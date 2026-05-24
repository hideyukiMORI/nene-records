<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

final readonly class ListEntityRelationsInput
{
    public function __construct(
        public int $entityId,
        public string $fieldKey,
    ) {
    }
}
