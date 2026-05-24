<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use RuntimeException;

final class RelationNotAttachedException extends RuntimeException
{
    public function __construct(
        public readonly int $entityId,
        public readonly int $targetEntityId,
        public readonly string $fieldKey,
    ) {
        parent::__construct(sprintf(
            'Target entity %d is not attached to entity %d for field key "%s".',
            $targetEntityId,
            $entityId,
            $fieldKey,
        ));
    }
}
