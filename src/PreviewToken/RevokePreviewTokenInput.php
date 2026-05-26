<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

final readonly class RevokePreviewTokenInput
{
    public function __construct(
        public int $entityId,
    ) {
    }
}
