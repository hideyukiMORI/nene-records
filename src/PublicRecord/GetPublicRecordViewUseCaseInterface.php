<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

interface GetPublicRecordViewUseCaseInterface
{
    public function execute(GetPublicRecordViewInput $input): GetPublicRecordViewOutput;
}
