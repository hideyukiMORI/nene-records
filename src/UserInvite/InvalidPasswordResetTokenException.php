<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

use DomainException;

final class InvalidPasswordResetTokenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Password reset token is invalid or has expired.');
    }
}
