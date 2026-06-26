<?php

declare(strict_types=1);

namespace NeNeRecords\Account;

interface GetAccountUseCaseInterface
{
    public function execute(int $organizationId): GetAccountOutput;
}
