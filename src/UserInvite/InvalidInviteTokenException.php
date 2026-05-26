<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

use DomainException;

final class InvalidInviteTokenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Invite token is invalid or has expired.');
    }
}
