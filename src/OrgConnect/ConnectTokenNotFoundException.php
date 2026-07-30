<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

use DomainException;

final class ConnectTokenNotFoundException extends DomainException
{
    public function __construct(public readonly string $service)
    {
        parent::__construct('Connect token not found: ' . $service);
    }
}
