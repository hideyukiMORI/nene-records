<?php

declare(strict_types=1);

namespace NeNeRecords\EntityArchive;

final readonly class GetEntityArchiveCsvInput
{
    public function __construct(
        public int $entityTypeId,
    ) {
    }
}
