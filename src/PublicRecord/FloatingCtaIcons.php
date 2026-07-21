<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * Curated first-party SVG icon set for the floating CTA (#982 P2).
 *
 * Admins pick an icon by kebab id ({@see FloatingCta::$iconId}); only these vetted,
 * repo-shipped icons are ever emitted — an org never supplies raw SVG, so there is no
 * `<script>`/`on*`/`foreignObject` XSS surface even though the CTA is emitted verbatim
 * into the (unsanitized) public shell. The validator's allowed set is derived from
 * {@see self::keys()} so the enum can never drift from these constants.
 *
 * Each entry is the INNER markup (paths only, ≤400B); {@see self::svg()} wraps it in a
 * uniform 24-viewBox, `currentColor`-stroked `<svg>` so line weight is consistent and
 * the FAB's text color is inherited. Only the one selected icon is ever rendered.
 */
final class FloatingCtaIcons
{
    /** @var array<string, string> id => inner svg markup (paths only). */
    private const PATHS = [
        'calendar' => '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>',
        'video' => '<rect x="2" y="6" width="13" height="12" rx="2"/><path d="M22 8.5 15 12l7 3.5z"/>',
        'chat' => '<path d="M21 11.5a8.5 8.5 0 0 1-12.3 7.6L3 21l1.9-5.7A8.5 8.5 0 1 1 21 11.5z"/>',
        'mail' => '<rect x="2" y="4.5" width="20" height="15" rx="2"/><path d="m3 6 9 7 9-7"/>',
        'phone' => '<path d="M6.6 3H4a1.9 1.9 0 0 0-1.9 2.1 16.9 16.9 0 0 0 14.8 14.8A1.9 1.9 0 0 0 19 18v-2.6a1.3 1.3 0 0 0-1-1.3l-3-.6a1.3 1.3 0 0 0-1.2.4l-1 1a13 13 0 0 1-5-5l1-1a1.3 1.3 0 0 0 .4-1.3l-.6-3A1.3 1.3 0 0 0 6.6 3z"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/>',
        'sparkle' => '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/>',
        'arrow-right' => '<path d="M4 12h15M13 6l6 6-6 6"/>',
    ];

    /**
     * Allowed icon ids — the single source the validator derives its enum from.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::PATHS);
    }

    public static function has(string $id): bool
    {
        return isset(self::PATHS[$id]);
    }

    /** Wrap the vetted inner markup in a uniform svg; '' for an unknown id. */
    public static function svg(string $id): string
    {
        if (!isset(self::PATHS[$id])) {
            return '';
        }

        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . self::PATHS[$id] . '</svg>';
    }
}
