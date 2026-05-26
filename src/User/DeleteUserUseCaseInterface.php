<?php

declare(strict_types=1);

namespace NeNeRecords\User;

interface DeleteUserUseCaseInterface
{
    public function execute(DeleteUserInput $input): void;
}
