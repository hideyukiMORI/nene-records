<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

final readonly class UpdateTextFieldInput
{
    public function __construct(
        public int $id,
        public string $fieldKey,
        public string $value,
        public ?string $locale = null,
    ) {
    }
}
