<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

final readonly class GetTagByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
