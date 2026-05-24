<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

interface UpdateSettingUseCaseInterface
{
    public function execute(UpdateSettingInput $input): UpdateSettingOutput;
}
