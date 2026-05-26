<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

use DomainException;

final class PreviewTokenNotFoundException extends DomainException
{
    public function __construct(string $token)
    {
        parent::__construct('Preview token not found or expired: ' . $token);
    }
}
