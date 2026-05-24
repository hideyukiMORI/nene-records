<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

final readonly class GetTextFieldByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
