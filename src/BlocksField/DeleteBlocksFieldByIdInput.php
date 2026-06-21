<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

final readonly class DeleteBlocksFieldByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
