<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

use RuntimeException;

final class EntityTagNotAttachedException extends RuntimeException
{
    public function __construct(
        public int $entityId,
        public int $tagId,
    ) {
        parent::__construct("Tag {$tagId} is not attached to entity {$entityId}.");
    }
}
