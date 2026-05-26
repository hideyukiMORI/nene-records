<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use DomainException;

final class InvalidCurrentPasswordException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Current password is incorrect.');
    }
}
