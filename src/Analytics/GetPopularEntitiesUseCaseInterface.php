<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

interface GetPopularEntitiesUseCaseInterface
{
    public function execute(GetPopularEntitiesInput $input): GetPopularEntitiesOutput;
}
