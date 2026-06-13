<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

/**
 * Whitelist of widget types. Widgets are bounded, typed blocks (not a free-form
 * page builder): each type has a known renderer and settings shape. Phase 1
 * ships `recent-posts`; Phase 2 adds `menu` (renders a navigation location);
 * Phase 3 adds `toc` (table of contents for the current page); Phase 4 adds
 * `search` (a search box linking to the results page); Phase 5 adds `tag-cloud`
 * (tags linking to tag archives); more (calendar, popular-posts) follow.
 */
final class WidgetTypes
{
    /** @var list<string> */
    private const TYPES = ['recent-posts', 'menu', 'toc', 'search', 'tag-cloud'];

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
