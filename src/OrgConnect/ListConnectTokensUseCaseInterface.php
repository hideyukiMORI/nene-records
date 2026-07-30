<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

interface ListConnectTokensUseCaseInterface
{
    public function execute(): ListConnectTokensOutput;
}
