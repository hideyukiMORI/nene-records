<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\User;

use NeNeRecords\User\UserProfile;
use NeNeRecords\User\UserProfileRepositoryInterface;

final class InMemoryUserProfileRepository implements UserProfileRepositoryInterface
{
    /** @var array<int, UserProfile> */
    private array $byUserId = [];

    public function findByUserId(int $userId): ?UserProfile
    {
        return $this->byUserId[$userId] ?? null;
    }

    public function upsert(
        int $userId,
        ?string $displayName,
        ?string $fullName,
        ?string $jobTitle,
    ): UserProfile {
        $now = time();
        $existing = $this->byUserId[$userId] ?? null;

        $profile = new UserProfile(
            userId: $userId,
            displayName: $displayName,
            fullName: $fullName,
            jobTitle: $jobTitle,
            createdAt: $existing !== null ? $existing->createdAt ?? $now : $now,
            updatedAt: $now,
        );

        $this->byUserId[$userId] = $profile;

        return $profile;
    }
}
