<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

/**
 * Whitelist of widget types. Widgets are bounded, typed blocks (not a free-form
 * page builder): each type has a known renderer and settings shape. Phase 1
 * ships `recent-posts`; Phase 2 adds `menu` (renders a navigation location);
 * Phase 3 adds `toc` (table of contents for the current page); Phase 4 adds
 * `search` (a search box linking to the results page); Phase 5 adds `tag-cloud`
 * (tags linking to tag archives), `popular-posts` (most-viewed records), and
 * `calendar` (a month grid linking to date archives).
 *
 * `trusted-embed` (#802) is the first-party trusted-embed primitive: a typed
 * widget that loads an external `<script>` from a **self-owned, admin-vetted**
 * origin (validated against the org's `embed_allowlist`, SRI required). Its
 * settings shape is validated by {@see TrustedEmbedSettings}.
 */
final class WidgetTypes
{
    /** @var list<string> */
    private const TYPES = [
        'recent-posts',
        'menu',
        'toc',
        'search',
        'tag-cloud',
        'popular-posts',
        'calendar',
        'trusted-embed',
    ];

    public static function isValid(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    /** @return list<string> */
    public static function all(): array
    {
        return self::TYPES;
    }
}
