<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\Http\EmbedAllowlist;
use NeNeRecords\Widget\TrustedEmbedSettings;
use NeNeRecords\Widget\Widget;
use NeNeRecords\Widget\WidgetRegions;

/**
 * Inline placement for `trusted-embed` widgets (#937): lets an author drop a
 * vetted embed at an **arbitrary point inside an `html` field's body**, instead
 * of only into the site chrome regions {@see TrustedEmbedScripts} covers.
 *
 * The author writes a marker — nothing else:
 *
 * ```html
 * <div data-nene-embed="12"></div>
 * ```
 *
 * where `12` is the id of a `trusted-embed` widget in the
 * {@see WidgetRegions::INLINE} region. The marker is *inert content*: it carries
 * no script, no URL and no integrity hash, so it is safe to author, safe to
 * import, and safe to leave in place when the widget is deleted.
 *
 * 🔑 **The sanitizer is not widened for scripts.** `<script>` in an `html` field
 * is still stripped unconditionally by {@see PublicHtmlSanitizer} (and DOMPurify
 * on the SPA side). The only concession is that the `data-nene-embed` attribute
 * survives on `div` — an inert hook, not an execution path. Substitution happens
 * **after** sanitizing, and the emitted tag is rebuilt by
 * {@see TrustedEmbedScripts::tagFor()} from the widget's *stored, re-validated*
 * settings — never from anything in the authored HTML. That is the same
 * generation discipline as #802: raw input can choose *where* an embed goes, but
 * never *what* it is.
 *
 * Every marker is resolved fail-closed. The marker is replaced with the empty
 * string — leaving no trace and emitting no script — when the referenced widget
 * does not exist, is not a `trusted-embed`, is not in the `inline` region, has
 * malformed settings, or has an origin absent from the org's `embed_allowlist`.
 * The same widget is emitted at most once per document even if referenced twice,
 * so a duplicated marker cannot double-load the script.
 *
 * A marker with content inside it (`<div data-nene-embed="1">x</div>`) is
 * deliberately **not** matched: only an empty marker is a placement. This keeps
 * the substitution a single, well-defined token replacement with no nesting
 * ambiguity, and leaves the rest of the document byte-for-byte untouched (no
 * DOM round-trip, so established bespoke pages cannot be reflowed by this pass).
 */
final class TrustedEmbedPlacements
{
    /** The inert marker attribute; allowed on `div` only (see PublicHtmlSanitizer). */
    public const ATTRIBUTE = 'data-nene-embed';

    /**
     * An empty `div` carrying `data-nene-embed="<digits>"` among its attributes.
     *
     * The match cannot span tags, and that holds **independently of what the
     * sanitizer does**: `[^>]*` stops at the first `>` by construction, so the
     * attribute run can never escape the tag it started in. Nothing here relies
     * on how attribute values happen to be escaped upstream — swapping the
     * sanitizer cannot invalidate this pattern.
     */
    private const MARKER_PATTERN =
        '#<div\b([^>]*\s)?' . self::ATTRIBUTE . '="(\d{1,18})"([^>]*)>\s*</div>#i';

    private function __construct()
    {
    }

    /**
     * Replace every inline marker in already-sanitized HTML with its validated
     * embed tag. Returns the input unchanged when it holds no marker, when the
     * org has no allowlist, or when no widget resolves.
     *
     * @param list<Widget> $widgets all of the org's widgets (any type / region)
     */
    public static function apply(string $sanitizedHtml, array $widgets, EmbedAllowlist $allowlist): string
    {
        if (!str_contains($sanitizedHtml, self::ATTRIBUTE)) {
            return $sanitizedHtml;
        }

        $emitted = [];

        $replaced = preg_replace_callback(
            self::MARKER_PATTERN,
            static function (array $m) use ($widgets, $allowlist, &$emitted): string {
                $id = (int) $m[2];

                // Emit a given widget at most once per document.
                if (isset($emitted[$id])) {
                    return '';
                }

                $spec = self::resolve($id, $widgets, $allowlist);
                if ($spec === null) {
                    return '';
                }

                // Marked only once it actually resolved, so `$emitted` means
                // "emitted" and not "seen": a marker that fails to resolve stays
                // a fresh decision for every later occurrence of the same id.
                $emitted[$id] = true;

                return TrustedEmbedScripts::inlineTagFor($spec);
            },
            $sanitizedHtml,
        );

        // preg_replace_callback returns null only on a PCRE failure (e.g. backtrack
        // limit on pathological input). Fail closed to the sanitized input rather
        // than emitting a half-substituted document.
        return $replaced ?? $sanitizedHtml;
    }

    /**
     * The validated spec of the inline `trusted-embed` widget with this id, or
     * `null` when it does not resolve under every #802 rule.
     *
     * @param list<Widget> $widgets
     */
    private static function resolve(int $id, array $widgets, EmbedAllowlist $allowlist): ?TrustedEmbedSettings
    {
        if ($allowlist->isEmpty()) {
            return null;
        }

        foreach ($widgets as $widget) {
            if ($widget->id !== $id) {
                continue;
            }
            if ($widget->widgetType !== 'trusted-embed') {
                return null;
            }
            // Only widgets parked in the inline region are placeable by marker;
            // a region-placed widget already renders in its region, and honouring
            // a marker for it would double-load the script.
            if ($widget->region !== WidgetRegions::INLINE) {
                return null;
            }

            $spec = TrustedEmbedSettings::tryParse($widget->settings);
            if ($spec === null) {
                return null;
            }

            // Independent allowlist gate (the CSP is the other layer) — identical
            // to the region path in TrustedEmbedScripts.
            return in_array($spec->origin, $allowlist->origins(), true) ? $spec : null;
        }

        return null;
    }
}
