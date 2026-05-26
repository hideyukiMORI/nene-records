<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

use NeNeRecords\PublicRecord\GetPublicRecordViewOutput;

interface GetPreviewRecordViewUseCaseInterface
{
    public function execute(GetPreviewRecordViewInput $input): GetPublicRecordViewOutput;
}
