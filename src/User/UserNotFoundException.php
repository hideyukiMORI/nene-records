<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use DomainException;

final class UserNotFoundException extends DomainException
{
    public function __construct(int $id)
    {
        parent::__construct(sprintf('User with id %d not found.', $id));
    }
}
