<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

interface AcceptInviteUseCaseInterface
{
    public function execute(AcceptInviteInput $input): void;
}
