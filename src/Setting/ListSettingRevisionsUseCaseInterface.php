<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

interface ListSettingRevisionsUseCaseInterface
{
    public function execute(ListSettingRevisionsInput $input): ListSettingRevisionsOutput;
}
