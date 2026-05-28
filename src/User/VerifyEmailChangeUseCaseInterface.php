<?php

declare(strict_types=1);

namespace NeNeRecords\User;

interface VerifyEmailChangeUseCaseInterface
{
    public function execute(VerifyEmailChangeInput $input): void;
}
