<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Deep-sanitises user-supplied SVG before it is stored and served (#474 / #373).
 *
 * SVG is the one allowed upload type that can carry executable content
 * (`<script>`, `on*` handlers, `javascript:` URIs, `<foreignObject>` HTML,
 * SMIL `<set attributeName="href" to="javascript:…">`). Media is served
 * same-origin and the JWT lives in localStorage, so an un-sanitised SVG is a
 * stored-XSS → token-theft vector.
 *
 * Strategy: parse as XML, reject DOCTYPE (kills XXE / entity-expansion), drop
 * forbidden elements, strip event handlers, dangerous URL schemes and external
 * references, and neutralise dangerous CSS. Fails closed — unparseable input or
 * a non-`<svg>` root throws {@see MediaInvalidTypeException}.
 *
 * Defence in depth only: {@see ServeMediaHandler} additionally sends
 * `X-Content-Type-Options: nosniff` and a locked-down CSP for SVG responses.
 */
final class SvgSanitizer
{
    /** Elements removed entirely, with their subtree. */
    private const FORBIDDEN_ELEMENTS = [
        'script', 'foreignobject', 'handler', 'iframe', 'embed', 'object', 'audio', 'video',
    ];

    /** Animation elements that can rewrite another attribute at runtime (SMIL injection). */
    private const ANIMATION_ELEMENTS = ['set', 'animate', 'animatetransform', 'animatemotion'];

    /** Elements whose URL references must stay in-document (`#id`) — external refs are dropped. */
    private const EXTERNAL_REF_ELEMENTS = [
        'use', 'image', 'feimage', 'a', 'filter', 'pattern',
        'lineargradient', 'radialgradient', 'textpath', 'mpath', 'tref',
    ];

    /**
     * @throws MediaInvalidTypeException when the input is not parseable SVG.
     */
    public function sanitize(string $svg): string
    {
        $svg = trim($svg);

        if ($svg === '') {
            throw new MediaInvalidTypeException('image/svg+xml');
        }

        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        // LIBXML_NONET blocks network access. We deliberately omit LIBXML_NOENT
        // so XML entities are never expanded into text.
        $loaded = $doc->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false || $doc->documentElement === null) {
            throw new MediaInvalidTypeException('image/svg+xml');
        }

        // Reject DOCTYPE outright — neutralises XXE and entity-expansion vectors.
        if ($doc->doctype !== null) {
            throw new MediaInvalidTypeException('image/svg+xml');
        }

        if (strtolower((string) $doc->documentElement->localName) !== 'svg') {
            throw new MediaInvalidTypeException('image/svg+xml');
        }

        $xpath = new DOMXPath($doc);

        // 1. Remove forbidden elements and SMIL animations targeting href/event attrs.
        /** @var list<DOMElement> $toRemove */
        $toRemove = [];
        foreach (($xpath->query('//*') ?: []) as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $local = strtolower((string) $node->localName);

            if (in_array($local, self::FORBIDDEN_ELEMENTS, true)) {
                $toRemove[] = $node;

                continue;
            }

            if (in_array($local, self::ANIMATION_ELEMENTS, true) && $this->animatesUnsafeAttribute($node)) {
                $toRemove[] = $node;
            }
        }
        foreach ($toRemove as $node) {
            $node->parentNode?->removeChild($node);
        }

        // 2. Scrub attributes on every remaining element.
        foreach (($xpath->query('//*') ?: []) as $node) {
            if ($node instanceof DOMElement) {
                $this->scrubAttributes($node);
            }
        }

        // 3. Neutralise dangerous CSS inside <style> elements.
        foreach (($xpath->query("//*[local-name()='style']") ?: []) as $style) {
            if ($style instanceof DOMElement && $this->hasDangerousCss($style->textContent)) {
                $style->textContent = '';
            }
        }

        $result = $doc->saveXML($doc->documentElement);

        if ($result === false) {
            throw new MediaInvalidTypeException('image/svg+xml');
        }

        return $result;
    }

    /**
     * True when the content is an SVG document (after an optional XML prolog,
     * comments and DOCTYPE). Used to catch SVG uploaded under a spoofed MIME.
     */
    public static function looksLikeSvg(string $content): bool
    {
        $head = ltrim($content, "\xEF\xBB\xBF \t\r\n");
        $head = preg_replace('/^<\?xml\b[^>]*\?>\s*/i', '', $head) ?? $head;
        $head = preg_replace('/^(?:<!--.*?-->\s*|<!DOCTYPE\b[^>]*>\s*)+/is', '', $head) ?? $head;

        return str_starts_with(strtolower(ltrim($head)), '<svg');
    }

    private function animatesUnsafeAttribute(DOMElement $element): bool
    {
        $target = strtolower($element->getAttribute('attributeName'));

        return $target === 'href' || $target === 'xlink:href' || str_starts_with($target, 'on');
    }

    private function scrubAttributes(DOMElement $element): void
    {
        $external = in_array(strtolower((string) $element->localName), self::EXTERNAL_REF_ELEMENTS, true);

        /** @var list<DOMAttr> $remove */
        $remove = [];
        foreach (iterator_to_array($element->attributes ?? []) as $attr) {
            $name = strtolower((string) $attr->localName);
            $value = $this->normalize($attr->value);

            // Event-handler attributes (onload, onclick, onbegin, …).
            if (str_starts_with($name, 'on')) {
                $remove[] = $attr;

                continue;
            }

            // URL-bearing attributes: drop dangerous schemes and external refs.
            if (in_array($name, ['href', 'src'], true)) {
                if ($this->isDangerousScheme($value) || ($external && $this->isExternal($value))) {
                    $remove[] = $attr;

                    continue;
                }
            }

            // Inline style carrying dangerous CSS.
            if ($name === 'style' && $this->hasDangerousCss($attr->value)) {
                $remove[] = $attr;

                continue;
            }

            // Catch-all: any attribute value carrying a script scheme (covers
            // SMIL to/from/values="javascript:…" and odd presentation attrs).
            if (str_contains($value, 'javascript:') || str_contains($value, 'vbscript:')) {
                $remove[] = $attr;
            }
        }

        foreach ($remove as $attr) {
            $element->removeAttributeNode($attr);
        }
    }

    /** Lower-cased, entity-decoded, whitespace/control-char-stripped value for scheme checks. */
    private function normalize(string $value): string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return strtolower((string) preg_replace('/[\s\x00-\x20]+/', '', $decoded));
    }

    private function isDangerousScheme(string $normalized): bool
    {
        return str_starts_with($normalized, 'javascript:')
            || str_starts_with($normalized, 'vbscript:')
            || str_starts_with($normalized, 'data:');
    }

    /** External = has a URI scheme or protocol-relative `//`; in-document `#id` and relative paths are kept. */
    private function isExternal(string $normalized): bool
    {
        return str_starts_with($normalized, '//') || (bool) preg_match('#^[a-z][a-z0-9+.\-]*:#', $normalized);
    }

    private function hasDangerousCss(string $css): bool
    {
        $value = strtolower(html_entity_decode($css, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if (str_contains($value, 'javascript:') || str_contains($value, 'expression(') || str_contains($value, '@import')) {
            return true;
        }

        // url(...) pointing at a scheme or protocol-relative target. url(#id) is safe.
        return (bool) preg_match('#url\(\s*["\']?\s*(?:[a-z][a-z0-9+.\-]*:|//)#i', $value);
    }
}
