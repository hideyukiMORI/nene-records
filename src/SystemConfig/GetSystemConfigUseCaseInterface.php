<?php

declare(strict_types=1);

namespace NeNeRecords\SystemConfig;

interface GetSystemConfigUseCaseInterface
{
    public function execute(): GetSystemConfigOutput;
}
