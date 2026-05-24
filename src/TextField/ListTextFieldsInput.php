<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

final readonly class ListTextFieldsInput
{
    public function __construct(
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}
