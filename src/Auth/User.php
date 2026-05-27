<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

final readonly class User
{
    public function __construct(
        public int $id,
        public string $email,
        public string $passwordHash,
        public string $role,
        public ?int $organizationId = null,
        public ?string $orgRole = null,
        public string $status = 'active',
        public ?string $inviteTokenHash = null,
        public ?int $inviteExpiresAt = null,
        public ?string $passwordResetTokenHash = null,
        public ?int $passwordResetExpiresAt = null,
        public ?int $createdAt = null,
        public ?int $updatedAt = null,
    ) {
    }
}
