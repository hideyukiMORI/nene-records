<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use RuntimeException;

final class RelationAlreadyAttachedException extends RuntimeException
{
    public function __construct(
        public readonly int $entityId,
        public readonly int $targetEntityId,
        public readonly string $fieldKey,
    ) {
        parent::__construct(sprintf(
            'Target entity %d is already attached to entity %d for field key "%s".',
            $targetEntityId,
            $entityId,
            $fieldKey,
        ));
    }
}
