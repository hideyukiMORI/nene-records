<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class MoveEntitySubtreeInput
{
    public function __construct(
        public int $entityId,
        /** The record's new custom permalink (its subtree follows). Already normalized. */
        public string $newPermalink,
    ) {
    }
}
