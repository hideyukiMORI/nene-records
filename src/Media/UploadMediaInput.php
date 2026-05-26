<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

final readonly class UploadMediaInput
{
    public function __construct(
        public string $tmpPath,
        public string $originalName,
        public string $mimeType,
        public int $size,
    ) {
    }
}
