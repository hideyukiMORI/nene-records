<?php

declare(strict_types=1);

namespace NeNeRecords\User;

interface ChangeOwnPasswordUseCaseInterface
{
    public function execute(ChangeOwnPasswordInput $input): void;
}
