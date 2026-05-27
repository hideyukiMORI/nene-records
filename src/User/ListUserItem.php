<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class ListUserItem
{
    public function __construct(
        public int $id,
        public string $email,
        public string $role,
        public ?int $organizationId,
        public ?string $orgRole,
        public string $status,
        public ?int $createdAt,
        public ?int $updatedAt,
    ) {
    }
}
