<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

final readonly class AttachEntityRelationOutput
{
    public function __construct(
        public string $fieldKey,
        public int $targetEntityId,
    ) {
    }
}
