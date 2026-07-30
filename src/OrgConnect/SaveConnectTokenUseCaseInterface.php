<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

interface SaveConnectTokenUseCaseInterface
{
    public function execute(SaveConnectTokenInput $input): SaveConnectTokenOutput;
}
