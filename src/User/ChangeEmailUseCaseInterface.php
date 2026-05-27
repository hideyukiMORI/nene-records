<?php

declare(strict_types=1);

namespace NeNeRecords\User;

interface ChangeEmailUseCaseInterface
{
    public function execute(ChangeEmailInput $input): void;
}
