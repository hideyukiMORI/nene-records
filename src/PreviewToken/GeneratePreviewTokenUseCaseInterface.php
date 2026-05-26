<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

interface GeneratePreviewTokenUseCaseInterface
{
    public function execute(GeneratePreviewTokenInput $input): GeneratePreviewTokenOutput;
}
