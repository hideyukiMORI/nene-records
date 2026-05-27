<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoUserProfileRepository implements UserProfileRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findByUserId(int $userId): ?UserProfile
    {
        $row = $this->query->fetchOne(
            'SELECT user_id, display_name, full_name, job_title,
                    UNIX_TIMESTAMP(created_at) AS created_at,
                    UNIX_TIMESTAMP(updated_at) AS updated_at
             FROM user_profiles WHERE user_id = ?',
            [$userId],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function upsert(
        int $userId,
        ?string $displayName,
        ?string $fullName,
        ?string $jobTitle,
    ): UserProfile {
        $existing = $this->findByUserId($userId);

        if ($existing === null) {
            $now = date('Y-m-d H:i:s');
            $this->query->execute(
                'INSERT INTO user_profiles (user_id, display_name, full_name, job_title, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$userId, $displayName, $fullName, $jobTitle, $now, $now],
            );
        } else {
            $this->query->execute(
                'UPDATE user_profiles SET display_name = ?, full_name = ?, job_title = ?, updated_at = NOW()
                 WHERE user_id = ?',
                [$displayName, $fullName, $jobTitle, $userId],
            );
        }

        $profile = $this->findByUserId($userId);

        assert($profile !== null);

        return $profile;
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): UserProfile
    {
        return new UserProfile(
            userId: (int) $row['user_id'],
            displayName: isset($row['display_name']) ? (string) $row['display_name'] : null,
            fullName: isset($row['full_name']) ? (string) $row['full_name'] : null,
            jobTitle: isset($row['job_title']) ? (string) $row['job_title'] : null,
            createdAt: isset($row['created_at']) ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (int) $row['updated_at'] : null,
        );
    }
}
