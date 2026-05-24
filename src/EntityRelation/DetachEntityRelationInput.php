<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

final readonly class DetachEntityRelationInput
{
    public function __construct(
        public int $entityId,
        public string $fieldKey,
        public int $targetEntityId,
    ) {
    }
}
