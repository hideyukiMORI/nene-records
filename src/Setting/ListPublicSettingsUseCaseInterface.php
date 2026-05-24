<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

interface ListPublicSettingsUseCaseInterface
{
    public function execute(): ListPublicSettingsOutput;
}
