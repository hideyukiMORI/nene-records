<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;
}
