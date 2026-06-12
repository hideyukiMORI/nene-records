<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

final readonly class UpdateMediaAltInput
{
    public function __construct(
        public int $id,
        public ?string $altText,
    ) {
    }
}
