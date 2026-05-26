<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use DomainException;

final class InvalidUserRoleException extends DomainException
{
    public function __construct(string $role)
    {
        parent::__construct(sprintf('Invalid role "%s". Valid roles are: admin, editor.', $role));
    }
}
