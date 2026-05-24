<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

final readonly class Tag
{
    public function __construct(
        public string $slug,
        public string $name,
        public ?int $id = null,
    ) {
    }
}
