<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

/** Outcome of executing a WXR import (S2/S3). */
final readonly class WxrImportResult
{
    /**
     * @param list<WxrImportSkippedItem> $skippedItems unsupported post_type/status (from the plan)
     * @param list<string>               $warnings
     */
    public function __construct(
        public int $createdEntities,
        public int $skippedExisting, // already present (idempotent re-import)
        public int $tagsEnsured,     // distinct tags found-or-created
        public int $tagLinks,
        public int $redirectsCreated, // 301 map entries (old WP URL → new permalink)
        public array $skippedItems,
        public array $warnings,
    ) {
    }
}
