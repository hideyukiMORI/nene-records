<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

interface ListSettingsUseCaseInterface
{
    public function execute(): ListSettingsOutput;
}
