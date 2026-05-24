<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

final readonly class CreateTextFieldInput
{
    public function __construct(
        public int $entityId,
        public string $fieldKey,
        public string $value,
    ) {
    }
}
