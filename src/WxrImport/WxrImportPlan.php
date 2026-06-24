<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

/**
 * A dry-run preview of a WXR import: what would be created, what is skipped, the
 * distinct tags, and warnings — surfaced before any write so the operator can
 * confirm. The execute step (a later slice) consumes the same mapping.
 */
final readonly class WxrImportPlan
{
    /**
     * @param list<WxrImportPlannedItem> $plannedItems
     * @param list<WxrImportSkippedItem> $skippedItems
     * @param list<string>               $tagSlugs distinct tags to be ensured
     * @param list<string>               $warnings
     * @param array<string, int>         $countsByEntityType posts/pages → count
     * @param array<string, int>         $countsByStatus     published/draft/scheduled → count
     */
    public function __construct(
        public array $plannedItems,
        public array $skippedItems,
        public array $tagSlugs,
        public array $warnings,
        public array $countsByEntityType,
        public array $countsByStatus,
    ) {
    }
}
