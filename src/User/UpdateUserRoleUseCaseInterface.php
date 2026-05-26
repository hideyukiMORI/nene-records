<?php

declare(strict_types=1);

namespace NeNeRecords\User;

interface UpdateUserRoleUseCaseInterface
{
    public function execute(UpdateUserRoleInput $input): UpdateUserRoleOutput;
}
