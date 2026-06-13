<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

/**
 * Regions a widget can be placed into. Unlike content field regions (which are
 * the record-layout columns main/sidebar/aside), widgets place into the site
 * chrome and side columns: `header` and `footer` are site-wide bars, `sidebar`
 * and `aside` are the secondary columns of multi-column record layouts. `main`
 * is reserved for record content and is not a widget target.
 */
final class WidgetRegions
{
    /** @var list<string> */
    private const REGIONS = ['header', 'sidebar', 'aside', 'footer'];

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
