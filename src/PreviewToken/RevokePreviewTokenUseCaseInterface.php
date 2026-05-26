<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

interface RevokePreviewTokenUseCaseInterface
{
    public function execute(RevokePreviewTokenInput $input): void;
}
