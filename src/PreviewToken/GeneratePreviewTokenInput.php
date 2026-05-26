<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

final readonly class GeneratePreviewTokenInput
{
    public function __construct(
        public int $entityId,
    ) {
    }
}
