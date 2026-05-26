<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

final readonly class GeneratePreviewTokenOutput
{
    public function __construct(
        public string $token,
        public string $expiresAtIso,
        public string $previewUrl,
    ) {
    }
}
