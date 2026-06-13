<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

/**
 * Whitelist of navigation locations. A single menu model carries a location so
 * the same CRUD drives the public header, footer, and side menus. `side` items
 * are surfaced on a page through a menu widget (see Widget `menu` type).
 */
final class NavLocations
{
    public const DEFAULT = 'header';

    /** @var list<string> */
    private const LOCATIONS = ['header', 'footer', 'side'];

    public static function isValid(string $location): bool
    {
        return in_array($location, self::LOCATIONS, true);
    }

    /** @return list<string> */
    public static function all(): array
    {
        return self::LOCATIONS;
    }
}
