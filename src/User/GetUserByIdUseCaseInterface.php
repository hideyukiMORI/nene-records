<?php

declare(strict_types=1);

namespace NeNeRecords\User;

interface GetUserByIdUseCaseInterface
{
    public function execute(GetUserByIdInput $input): GetUserByIdOutput;
}
