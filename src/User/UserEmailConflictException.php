<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use DomainException;

final class UserEmailConflictException extends DomainException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('A user with email "%s" already exists.', $email));
    }
}
