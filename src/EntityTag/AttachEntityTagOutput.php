<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

final readonly class AttachEntityTagOutput
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
    ) {
    }
}
