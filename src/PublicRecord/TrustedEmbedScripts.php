<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\Http\EmbedAllowlist;
use NeNeRecords\Widget\TrustedEmbedSettings;
use NeNeRecords\Widget\Widget;

/**
 * First-party server-side renderer for `trusted-embed` widgets (#802): turns a
 * validated, allowlisted embed into the actual
 * `<script src integrity crossorigin="anonymous" async>` tag — the one script
 * output path on the public surface that is **not** the content sanitizer.
 *
 * A trusted-embed is inherently JavaScript, so its live runtime is the SPA
 * ({@see \NeNeRecords\Widget\ListPublicWidgetsHandler} → `TrustedEmbedWidget`),
 * which fully replaces the crawlable `#root` shell on mount. This SSR renderer
 * emits the identical validated tag into that shell inside a `<noscript>` block:
 *
 * - It keeps the SSR path at strict parity with the SPA — same allowlist +
 *   origin + SRI gate, same first-party template (not the sanitizer) — so
 *   neither path can become a bypass.
 * - Wrapping in `<noscript>` means the tag never *executes* twice: with JS on
 *   the SPA owns the embed and the `<noscript>` body is inert; with JS off the
 *   embed cannot run anyway. This removes any SSR→SPA double-load.
 *
 * Every widget is re-validated here regardless of what was stored:
 * - malformed settings ({@see TrustedEmbedSettings::tryParse} returns null),
 * - an origin **not present in the org's `embed_allowlist`**, or
 * - (implicitly) a `src` whose origin does not match / a missing SRI
 * ⇒ the widget is **skipped** (rendered as nothing). Defense in depth: the CSP
 * only lists allowlisted origins, and this renderer independently refuses any
 * embed whose origin is not allowlisted.
 *
 * With an empty allowlist, or no `trusted-embed` widgets, the output is the empty
 * string — the caller emits nothing and the public page is byte-for-byte
 * unchanged.
 */
final class TrustedEmbedScripts
{
    private function __construct()
    {
    }

    /**
     * @param list<Widget> $widgets all of the org's widgets (any type / region)
     */
    public static function render(array $widgets, EmbedAllowlist $allowlist): string
    {
        if ($allowlist->isEmpty()) {
            return '';
        }

        $allowedOrigins = $allowlist->origins();
        $tags = [];

        foreach ($widgets as $widget) {
            if ($widget->widgetType !== 'trusted-embed') {
                continue;
            }

            $spec = TrustedEmbedSettings::tryParse($widget->settings);
            if ($spec === null) {
                continue;
            }

            // Independent allowlist gate (the CSP is the other layer).
            if (!in_array($spec->origin, $allowedOrigins, true)) {
                continue;
            }

            $tags[] = self::tag($spec);
        }

        if ($tags === []) {
            return '';
        }

        // Inert shell copy: present for parity + crawlable markup, never a second
        // execution (the SPA is the live runtime — see class docblock).
        return '<noscript data-nene-trusted-embed>' . implode('', $tags) . '</noscript>';
    }

    private static function tag(TrustedEmbedSettings $spec): string
    {
        $attrs = '';
        foreach ($spec->attributes as $name => $value) {
            // $name already matched /^data-[a-z0-9-]+$/ in tryParse; escape the value.
            $attrs .= ' ' . $name . '="' . self::esc($value) . '"';
        }

        return '<script'
            . ' src="' . self::esc($spec->src) . '"'
            . ' integrity="' . self::esc($spec->integrity) . '"'
            . ' crossorigin="anonymous"'
            . ' async'
            . $attrs
            . '></script>';
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
