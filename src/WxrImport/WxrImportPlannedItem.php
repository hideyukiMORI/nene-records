<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

/** An item that would be imported, with its resolved NeNe mapping. */
final readonly class WxrImportPlannedItem
{
    /** @param list<string> $tagSlugs */
    public function __construct(
        public string $title,
        public string $slug,
        public string $entityTypeSlug, // 'posts' | 'pages'
        public string $status,         // 'published' | 'draft' | 'scheduled'
        public array $tagSlugs,
    ) {
    }
}
