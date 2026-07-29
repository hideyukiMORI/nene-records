<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\EntityType\SeoPageKind;
use NeNeRecords\Layout\PublicLayouts;

final readonly class GetPublicRecordViewOutput
{
    /**
     * @param array<string, mixed> $bootstrap
     * @param list<PublicRecordViewDisplayField> $displayFields
     * @param list<PublicRecordBreadcrumb> $breadcrumbs
     * @param list<PublicRecordChildLink> $childPages
     */
    public function __construct(
        public string $entityTypeSlug,
        public string $entityTypeName,
        public int $entityId,
        public string $entitySlug,
        public string $pageTitle,
        public string $metaDescription,
        public string $canonicalPath,
        public ?string $ogImagePath,
        public ?string $publishedAtIso,
        public ?string $updatedAtIso,
        public array $bootstrap,
        public array $displayFields,
        public ?PublicRecordChapterNav $chapterNav = null,
        public array $breadcrumbs = [],
        public array $childPages = [],
        /**
         * Effective page layout (`PublicLayouts::resolve`: entity override → type
         * default → `standard`). The SSR must honour it or it renders chrome the SPA
         * then removes — visible as a flash, and permanent for crawlers (#879).
         */
        public string $layout = PublicLayouts::DEFAULT,
        /**
         * How this record's type presents itself to crawlers (#1020) — decides
         * `og:type` and the JSON-LD `@type`. Comes from the entity type, not the
         * record: "is this a catalogue of pages or a stream of dated posts?" is a
         * property of the type. Rendering as the front page overrides it (a pinned
         * record is the site home whatever its type says).
         */
        public SeoPageKind $seoPageKind = SeoPageKind::WebPage,
    ) {
    }
}
