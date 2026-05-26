<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

interface ScheduleEntityUseCaseInterface
{
    public function execute(ScheduleEntityInput $input): ScheduleEntityOutput;
}
