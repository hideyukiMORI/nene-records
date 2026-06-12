<?php

declare(strict_types=1);

namespace NeNeRecords\Layout;

/**
 * Whitelist of content regions a field can be placed into. Which regions a page
 * actually renders depends on its layout (see PublicLayouts::regions); fields in
 * a region the active layout does not render fall back to `main`.
 */
final class ContentRegions
{
    public const DEFAULT = 'main';

    /** @var list<string> */
    private const REGIONS = ['main', 'sidebar', 'aside'];

    public static function isValid(string $region): bool
    {
        return in_array($region, self::REGIONS, true);
    }

    /** @return list<string> */
    public static function all(): array
    {
        return self::REGIONS;
    }
}
