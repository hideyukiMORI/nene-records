<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

/**
 * Where a named menu auto-displays. `header`/`footer` render in the public site
 * chrome; `null` means the menu only appears where a menu widget references it.
 */
final class MenuLocations
{
    /** @var list<string> */
    private const LOCATIONS = ['header', 'footer'];

    public static function isValid(?string $location): bool
    {
        return $location === null || in_array($location, self::LOCATIONS, true);
    }

    /** @return list<string> */
    public static function all(): array
    {
        return self::LOCATIONS;
    }
}
