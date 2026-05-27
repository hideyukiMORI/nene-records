<?php

declare(strict_types=1);

namespace NeNeRecords\SystemConfig;

interface UpdateSystemConfigUseCaseInterface
{
    public function execute(UpdateSystemConfigInput $input): UpdateSystemConfigOutput;
}
