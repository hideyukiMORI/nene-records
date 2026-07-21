<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * Resolved floating-CTA configuration for the public surface (EPIC #982, P1).
 *
 * A first-party, org-scoped "site chrome" element: a fixed floating call-to-action
 * button rendered by the public SSR shell on every matching page (see
 * {@see FloatingCtaHtml}). Unlike a button embedded in a record's `html` field, this
 * is emitted verbatim into the shell — NOT through {@see PublicHtmlSanitizer} — so it
 * survives with real CSS/hover/media (the reason it exists as a feature). All
 * admin-supplied values are still validated on write ({@see \NeNeRecords\Setting\FloatingCtaValidator})
 * and escaped on render, so verbatim output is safe.
 *
 * Sourced from the public setting `floating_cta` (a JSON blob). A stored value that
 * does not parse, or is `enabled:false`, resolves to a disabled config (renders '').
 *
 * P1 scope (hub 2026-07-22): structured content only (emoji icon + label + sub),
 * position presets `br`/`bl`, trigger `always`, condition matching by type / URL glob.
 * `right-tab`/`bottom-bar`, scroll/delay triggers, images and raw HTML are P2/P3 and
 * are rejected at the validation boundary — they never reach here.
 */
final readonly class FloatingCta
{
    private const DEFAULT_ACCENT = '#1f6feb';

    /**
     * @param 'br'|'bl'          $position
     * @param list<string>       $conditionTypes    entity type slugs; empty = all types
     * @param list<string>       $conditionUrlGlobs path globs to include; empty = all paths
     * @param list<string>       $conditionExclude  path globs to exclude (wins over include)
     */
    public function __construct(
        public bool $enabled,
        public string $position,
        public string $accent,
        public string $icon,
        public string $label,
        public string $sub,
        public string $url,
        public bool $newTab,
        public array $conditionTypes,
        public array $conditionUrlGlobs,
        public array $conditionExclude,
    ) {
    }

    public static function disabled(): self
    {
        return new self(false, 'br', self::DEFAULT_ACCENT, '', '', '', '', true, [], [], []);
    }

    /**
     * Build from a flat `settingKey => effectiveValue` map (public settings).
     *
     * Defensive: any parse/shape problem yields a disabled config rather than throwing,
     * so a malformed stored value can never break page rendering (the write path is the
     * validation boundary; this is a belt-and-suspenders read).
     *
     * @param array<string, string> $settings
     */
    public static function fromSettings(array $settings): self
    {
        $raw = $settings['floating_cta'] ?? '';
        if (trim($raw) === '') {
            return self::disabled();
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return self::disabled();
        }

        $enabled = ($decoded['enabled'] ?? false) === true;
        if (!$enabled) {
            return self::disabled();
        }

        $position = $decoded['position'] ?? 'br';
        if ($position !== 'br' && $position !== 'bl') {
            $position = 'br';
        }

        $accent = is_string($decoded['accent'] ?? null) && preg_match('/^#[0-9A-Fa-f]{6}$/', $decoded['accent']) === 1
            ? $decoded['accent']
            : self::DEFAULT_ACCENT;

        $content = is_array($decoded['content'] ?? null) ? $decoded['content'] : [];
        $link = is_array($decoded['link'] ?? null) ? $decoded['link'] : [];
        $conditions = is_array($decoded['conditions'] ?? null) ? $decoded['conditions'] : [];

        $label = self::asString($content['label'] ?? '');
        $url = self::asString($link['url'] ?? '');

        // A CTA with no label / no link, or an unsafe link scheme, cannot render safely —
        // treat as disabled. The validator rejects these on write; this read-side re-check
        // means a legacy/hand-edited row can never emit an unsafe `href` into the shell.
        if ($label === '' || $url === '' || !self::isSafeUrl($url)) {
            return self::disabled();
        }

        return new self(
            enabled: true,
            position: $position,
            accent: $accent,
            icon: self::asString($content['icon'] ?? ''),
            label: $label,
            sub: self::asString($content['sub'] ?? ''),
            url: $url,
            newTab: ($link['newTab'] ?? true) !== false,
            conditionTypes: self::asStringList($conditions['types'] ?? []),
            conditionUrlGlobs: self::asStringList($conditions['urlGlobs'] ?? []),
            conditionExclude: self::asStringList($conditions['exclude'] ?? []),
        );
    }

    /**
     * Whether the CTA should appear on the current public page.
     *
     * Rules (P1): include when the type is allowed (empty types = all) AND the path
     * matches an include glob (empty globs = all); an exclude-glob match always wins.
     *
     * @param string $typeSlug current entity/type slug ('' when not applicable)
     * @param string $path     request path (e.g. '/', '/services', '/blog/x')
     */
    public function shouldRender(string $typeSlug, string $path): bool
    {
        if (!$this->enabled) {
            return false;
        }

        foreach ($this->conditionExclude as $glob) {
            if (self::globMatches($glob, $path)) {
                return false;
            }
        }

        if ($this->conditionTypes !== [] && !in_array($typeSlug, $this->conditionTypes, true)) {
            return false;
        }

        if ($this->conditionUrlGlobs !== []) {
            foreach ($this->conditionUrlGlobs as $glob) {
                if (self::globMatches($glob, $path)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Allowlist safe link targets; blocks `javascript:`/`data:` (script execution) and
     * protocol-relative `//host` / backslash-authority forms (open redirect / phishing).
     * Mirrors the write-side check in {@see \NeNeRecords\Setting\FloatingCtaValidator};
     * P1 allows http(s) / mailto / tel / site-relative (`/`, `#`).
     */
    public static function isSafeUrl(string $url): bool
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return false;
        }

        // protocol-relative `//host` or backslash-authority `/\host` / `\\host`
        $first = $trimmed[0];
        $second = $trimmed[1] ?? '';
        if (($first === '/' || $first === '\\') && ($second === '/' || $second === '\\')) {
            return false;
        }

        return preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $trimmed) === 1;
    }

    /** Simple `*` wildcard glob (matches any run of chars); anchored full-path match. */
    private static function globMatches(string $glob, string $path): bool
    {
        $trimmed = trim($glob);
        if ($trimmed === '') {
            return false;
        }

        $pattern = '#^' . str_replace('\*', '.*', preg_quote($trimmed, '#')) . '$#';

        return preg_match($pattern, $path) === 1;
    }

    private static function asString(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    /**
     * @return list<string>
     */
    private static function asStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return $out;
    }
}
