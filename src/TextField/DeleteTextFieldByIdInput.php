<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

final readonly class DeleteTextFieldByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
