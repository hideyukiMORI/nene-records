<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->query->fetchOne(
            'SELECT id, email, password_hash, role, status,
                    invite_token_hash, invite_expires_at,
                    password_reset_token_hash, password_reset_expires_at,
                    UNIX_TIMESTAMP(created_at) AS created_at,
                    UNIX_TIMESTAMP(updated_at) AS updated_at
             FROM users WHERE email = ?',
            [$email],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function findById(int $id): ?User
    {
        $row = $this->query->fetchOne(
            'SELECT id, email, password_hash, role, status,
                    invite_token_hash, invite_expires_at,
                    password_reset_token_hash, password_reset_expires_at,
                    UNIX_TIMESTAMP(created_at) AS created_at,
                    UNIX_TIMESTAMP(updated_at) AS updated_at
             FROM users WHERE id = ?',
            [$id],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    /** @return list<User> */
    public function list(): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, email, password_hash, role, status,
                    invite_token_hash, invite_expires_at,
                    password_reset_token_hash, password_reset_expires_at,
                    UNIX_TIMESTAMP(created_at) AS created_at,
                    UNIX_TIMESTAMP(updated_at) AS updated_at
             FROM users ORDER BY id ASC',
            [],
        );

        return array_map($this->mapRow(...), $rows);
    }

    public function create(string $email, string $passwordHash, string $role): User
    {
        $now = date('Y-m-d H:i:s');
        $this->query->execute(
            'INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$email, $passwordHash, $role, 'active', $now, $now],
        );

        $user = $this->findByEmail($email);

        assert($user !== null);

        return $user;
    }

    public function updateRole(int $id, string $role): void
    {
        $this->query->execute(
            'UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?',
            [$role, $id],
        );
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->query->execute(
            'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?',
            [$passwordHash, $id],
        );
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->query->execute(
            'UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?',
            [$status, $id],
        );
    }

    public function storeInviteToken(int $id, string $tokenHash, int $expiresAt): void
    {
        $expiresAtStr = date('Y-m-d H:i:s', $expiresAt);
        $this->query->execute(
            'UPDATE users SET invite_token_hash = ?, invite_expires_at = ?, status = ?, updated_at = NOW() WHERE id = ?',
            [$tokenHash, $expiresAtStr, 'invited', $id],
        );
    }

    public function findByInviteToken(string $tokenHash): ?User
    {
        $row = $this->query->fetchOne(
            'SELECT id, email, password_hash, role, status,
                    invite_token_hash, invite_expires_at,
                    password_reset_token_hash, password_reset_expires_at,
                    UNIX_TIMESTAMP(created_at) AS created_at,
                    UNIX_TIMESTAMP(updated_at) AS updated_at
             FROM users WHERE invite_token_hash = ?',
            [$tokenHash],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function clearInviteToken(int $id): void
    {
        $this->query->execute(
            'UPDATE users SET invite_token_hash = NULL, invite_expires_at = NULL, status = ?, updated_at = NOW() WHERE id = ?',
            ['active', $id],
        );
    }

    public function storePasswordResetToken(int $id, string $tokenHash, int $expiresAt): void
    {
        $expiresAtStr = date('Y-m-d H:i:s', $expiresAt);
        $this->query->execute(
            'UPDATE users SET password_reset_token_hash = ?, password_reset_expires_at = ?, updated_at = NOW() WHERE id = ?',
            [$tokenHash, $expiresAtStr, $id],
        );
    }

    public function findByPasswordResetToken(string $tokenHash): ?User
    {
        $row = $this->query->fetchOne(
            'SELECT id, email, password_hash, role, status,
                    invite_token_hash, invite_expires_at,
                    password_reset_token_hash, password_reset_expires_at,
                    UNIX_TIMESTAMP(created_at) AS created_at,
                    UNIX_TIMESTAMP(updated_at) AS updated_at
             FROM users WHERE password_reset_token_hash = ?',
            [$tokenHash],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function clearPasswordResetToken(int $id): void
    {
        $this->query->execute(
            'UPDATE users SET password_reset_token_hash = NULL, password_reset_expires_at = NULL, updated_at = NOW() WHERE id = ?',
            [$id],
        );
    }

    public function delete(int $id): void
    {
        $this->query->execute('DELETE FROM users WHERE id = ?', [$id]);
    }

    public function countByRole(string $role): int
    {
        $row = $this->query->fetchOne(
            'SELECT COUNT(*) AS cnt FROM users WHERE role = ?',
            [$role],
        );

        return (int) ($row['cnt'] ?? 0);
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): User
    {
        return new User(
            id: (int) $row['id'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            role: (string) $row['role'],
            status: (string) ($row['status'] ?? 'active'),
            inviteTokenHash: isset($row['invite_token_hash']) ? (string) $row['invite_token_hash'] : null,
            inviteExpiresAt: isset($row['invite_expires_at']) ? (int) $row['invite_expires_at'] : null,
            passwordResetTokenHash: isset($row['password_reset_token_hash']) ? (string) $row['password_reset_token_hash'] : null,
            passwordResetExpiresAt: isset($row['password_reset_expires_at']) ? (int) $row['password_reset_expires_at'] : null,
            createdAt: isset($row['created_at']) ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (int) $row['updated_at'] : null,
        );
    }
}
