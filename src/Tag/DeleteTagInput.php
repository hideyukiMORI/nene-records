<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

final readonly class DeleteTagInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
