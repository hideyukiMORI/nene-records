<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class CreateUserOutput
{
    public function __construct(
        public int $id,
        public string $email,
        public string $role,
        public string $status,
        public ?int $createdAt,
    ) {
    }
}
