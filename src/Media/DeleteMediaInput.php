<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

final readonly class DeleteMediaInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
