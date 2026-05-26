<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

final readonly class GetPreviewRecordViewInput
{
    public function __construct(
        public string $token,
    ) {
    }
}
