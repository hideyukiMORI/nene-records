<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

final readonly class PublicRecordViewDisplayField
{
    /**
     * @param list<array{label: string, href: string}> $relationLinks
     */
    public function __construct(
        public string $fieldKey,
        public string $dataType,
        public string $displayValue,
        public array $relationLinks = [],
    ) {
    }
}
