<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

interface ConfirmPasswordResetUseCaseInterface
{
    public function execute(ConfirmPasswordResetInput $input): void;
}
