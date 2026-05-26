<?php

declare(strict_types=1);

namespace NeNeRecords\User;

interface AdminResetPasswordUseCaseInterface
{
    public function execute(AdminResetPasswordInput $input): void;
}
