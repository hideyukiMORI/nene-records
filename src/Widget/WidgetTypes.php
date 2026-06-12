<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

/**
 * Whitelist of widget types. Widgets are bounded, typed blocks (not a free-form
 * page builder): each type has a known renderer and settings shape. Phase 1
 * ships `recent-posts`; more (search, menu, calendar, tag-cloud) follow.
 */
final class WidgetTypes
{
    /** @var list<string> */
    private const TYPES = ['recent-posts'];

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
