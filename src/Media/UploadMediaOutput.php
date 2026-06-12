<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

final readonly class UploadMediaOutput
{
    public function __construct(
        public int $id,
        public string $url,
        public string $originalName,
        public string $mimeType,
        public int $size,
        public string $createdAt = '',
        public ?int $width = null,
        public ?int $height = null,
        public ?string $altText = null,
    ) {
    }
}
