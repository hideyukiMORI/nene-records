<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

final readonly class GetBlocksFieldByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
