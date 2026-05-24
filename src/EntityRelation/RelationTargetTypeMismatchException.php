<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use RuntimeException;

final class RelationTargetTypeMismatchException extends RuntimeException
{
    public function __construct(
        public readonly string $fieldKey,
        public readonly int $expectedEntityTypeId,
        public readonly int $actualEntityTypeId,
    ) {
        parent::__construct(sprintf(
            'Relation field "%s" requires target entity type id %d but target has type id %d.',
            $fieldKey,
            $expectedEntityTypeId,
            $actualEntityTypeId,
        ));
    }
}
