<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

final readonly class CreateTextFieldOutput
{
    public function __construct(
        public int $id,
        public int $entityId,
        public string $fieldKey,
        public string $value,
        public ?string $locale = null,
    ) {
    }
}
