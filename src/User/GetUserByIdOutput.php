<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class GetUserByIdOutput
{
    public function __construct(
        public int $id,
        public string $email,
        public string $role,
        public ?int $organizationId,
        public ?string $orgRole,
        public string $status,
        public ?string $pendingEmail,
        public ?string $displayName,
        public ?string $fullName,
        public ?string $jobTitle,
        public ?int $createdAt,
        public ?int $updatedAt,
    ) {
    }
}
