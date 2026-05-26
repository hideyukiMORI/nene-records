<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class UpdateUserRoleOutput
{
    public function __construct(
        public int $id,
        public string $email,
        public string $role,
        public string $status,
    ) {
    }
}
