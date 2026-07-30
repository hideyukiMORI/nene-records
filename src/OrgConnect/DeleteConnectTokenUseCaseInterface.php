<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

interface DeleteConnectTokenUseCaseInterface
{
    public function execute(DeleteConnectTokenInput $input): void;
}
