<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class MoveEntitySubtreeOutput
{
    public function __construct(
        public int $entityId,
        public string $permalink,
        /** Records whose permalink was rewritten (root + descendants); 0 = no-op. */
        public int $movedCount,
    ) {
    }
}
