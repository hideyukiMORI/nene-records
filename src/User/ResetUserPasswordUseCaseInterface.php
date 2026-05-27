<?php

declare(strict_types=1);

namespace NeNeRecords\User;

interface ResetUserPasswordUseCaseInterface
{
    public function execute(ResetUserPasswordInput $input): void;
}
