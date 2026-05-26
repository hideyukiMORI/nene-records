<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

interface ProcessScheduledPublishUseCaseInterface
{
    public function execute(): ProcessScheduledPublishOutput;
}
