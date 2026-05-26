<?php

declare(strict_types=1);

namespace NeNeRecords\User;

interface CreateUserUseCaseInterface
{
    public function execute(CreateUserInput $input): CreateUserOutput;
}
