<?php

declare(strict_types=1);

namespace NeNeRecords\User;

interface UpdateUserProfileUseCaseInterface
{
    public function execute(UpdateUserProfileInput $input): UpdateUserProfileOutput;
}
