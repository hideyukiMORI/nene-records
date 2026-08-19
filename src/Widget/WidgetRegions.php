<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

/**
 * Regions a widget can be placed into. Unlike content field regions (which are
 * the record-layout columns main/sidebar/aside), widgets place into the site
 * chrome and side columns: `header` and `footer` are site-wide bars, `sidebar`
 * and `aside` are the secondary columns of multi-column record layouts. `main`
 * is reserved for record content and is not a widget target.
 *
 * {@see INLINE} is the one region that is not a place on the page: a widget
 * parked there renders **nowhere** on its own, and appears only where an `html`
 * field's body references it by marker (#937 — see
 * {@see \NeNeRecords\PublicRecord\TrustedEmbedPlacements}). It is what lets an
 * embed sit at an arbitrary point in the content instead of in the chrome.
 */
final class WidgetRegions
{
    /** Not a place on the page: rendered only where content references it by marker (#937). */
    public const INLINE = 'inline';

    /** @var list<string> */
    private const REGIONS = ['header', 'sidebar', 'aside', 'footer', self::INLINE];

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
