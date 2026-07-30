<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

final readonly class DeleteConnectTokenInput
{
    public function __construct(
        public ConnectTokenService $service,
    ) {
    }
}
