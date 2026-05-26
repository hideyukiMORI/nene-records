<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class UpdateUserRoleInput
{
    public function __construct(
        public int $id,
        public string $role,
    ) {
    }
}
