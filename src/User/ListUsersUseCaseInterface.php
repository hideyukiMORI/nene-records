<?php

declare(strict_types=1);

namespace NeNeRecords\User;

interface ListUsersUseCaseInterface
{
    public function execute(ListUsersInput $input): ListUsersOutput;
}
