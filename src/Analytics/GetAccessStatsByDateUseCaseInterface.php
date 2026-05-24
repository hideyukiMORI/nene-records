<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

interface GetAccessStatsByDateUseCaseInterface
{
    public function execute(GetAccessStatsByDateInput $input): GetAccessStatsByDateOutput;
}
