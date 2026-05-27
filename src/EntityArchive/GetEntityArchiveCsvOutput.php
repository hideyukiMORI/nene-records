<?php

declare(strict_types=1);

namespace NeNeRecords\EntityArchive;

final readonly class GetEntityArchiveCsvOutput
{
    /**
     * @param list<ArchivedEntity> $rows
     */
    public function __construct(
        public int $entityTypeId,
        public array $rows,
    ) {
    }
}
