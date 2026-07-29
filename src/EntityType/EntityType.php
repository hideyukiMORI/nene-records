<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class EntityType
{
    /**
     * @param array<string, string>|null $labels             Locale-keyed display names.
     * @param string|null                $permalinkPattern   URL pattern for public records.
     *                                                        Tokens: {type} {slug} {id} {year} {month} {day}
     *                                                        Null = use default "/{type}/{id}".
     * @param SeoPageKind                $seoPageKind        How this type's records present
     *                                                        themselves to crawlers (#1020).
     *                                                        New types default to a standing page;
     *                                                        see {@see SeoPageKind} for why that
     *                                                        direction is the safer default.
     */
    public function __construct(
        public string $name,
        public string $slug,
        public bool $isPinned = false,
        public ?int $id = null,
        public ?array $labels = null,
        public ?string $permalinkPattern = null,
        public ?string $previousPermalinkPattern = null,
        public int $displayOrder = 0,
        public string $defaultLayout = 'standard',
        public SeoPageKind $seoPageKind = SeoPageKind::WebPage,
    ) {
    }
}
