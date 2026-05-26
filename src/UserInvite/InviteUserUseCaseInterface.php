<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

interface InviteUserUseCaseInterface
{
    public function execute(InviteUserInput $input): InviteUserOutput;
}
