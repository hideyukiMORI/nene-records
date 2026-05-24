<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

final readonly class CreateTagInput
{
    public function __construct(
        public string $name,
        public string $slug,
    ) {
    }
}
