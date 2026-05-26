<?php

declare(strict_types=1);

namespace NeNeRecords\Dashboard;

interface GetDashboardSummaryUseCaseInterface
{
    public function execute(): GetDashboardSummaryOutput;
}
