<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

final readonly class Media
{
    public function __construct(
        public ?int $id,
        public string $originalName,
        public string $storedName,
        public string $mimeType,
        public int $size,
        public string $url,
        public string $createdAt,
    ) {
    }
}
