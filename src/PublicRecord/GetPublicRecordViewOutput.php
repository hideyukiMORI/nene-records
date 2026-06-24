<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

final readonly class GetPublicRecordViewOutput
{
    /**
     * @param array<string, mixed> $bootstrap
     * @param list<PublicRecordViewDisplayField> $displayFields
     */
    public function __construct(
        public string $entityTypeSlug,
        public string $entityTypeName,
        public int $entityId,
        public string $entitySlug,
        public string $pageTitle,
        public string $metaDescription,
        public string $canonicalPath,
        public ?string $publishedAtIso,
        public ?string $updatedAtIso,
        public array $bootstrap,
        public array $displayFields,
    ) {
    }
}
