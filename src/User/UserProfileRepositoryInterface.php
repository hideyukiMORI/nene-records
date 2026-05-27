<?php

declare(strict_types=1);

namespace NeNeRecords\User;

interface UserProfileRepositoryInterface
{
    public function findByUserId(int $userId): ?UserProfile;

    public function upsert(
        int $userId,
        ?string $displayName,
        ?string $fullName,
        ?string $jobTitle,
    ): UserProfile;
}
