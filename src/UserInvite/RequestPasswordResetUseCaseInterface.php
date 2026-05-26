<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

interface RequestPasswordResetUseCaseInterface
{
    /**
     * Initiates a password reset for the given email.
     * Always returns silently even if the email is not found (prevents email enumeration).
     */
    public function execute(RequestPasswordResetInput $input): void;
}
