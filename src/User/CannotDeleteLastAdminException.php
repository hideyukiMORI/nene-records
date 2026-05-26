<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use DomainException;

final class CannotDeleteLastAdminException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Cannot delete the last admin user.');
    }
}
