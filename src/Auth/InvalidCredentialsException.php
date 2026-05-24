<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

use DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid email or password.');
    }
}
