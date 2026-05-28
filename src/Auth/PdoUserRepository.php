<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoUserRepository implements UserRepositoryInterface
{
    private const SELECT_COLUMNS = '
        id, email, password_hash, role, organization_id, org_role, status,
        invite_token_hash, invite_expires_at,
        password_reset_token_hash, password_reset_expires_at,
        pending_email, email_verification_token_hash,
        UNIX_TIMESTAMP(email_verification_expires_at) AS email_verification_expires_at,
        UNIX_TIMESTAMP(created_at) AS created_at,
        UNIX_TIMESTAMP(updated_at) AS updated_at
    ';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::SELECT_COLUMNS . ' FROM users WHERE email = ?',
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
            'SELECT ' . self::SELECT_COLUMNS . ' FROM users WHERE id = ?',
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
            'SELECT ' . self::SELECT_COLUMNS . ' FROM users ORDER BY id ASC',
            [],
        );

        return array_map($this->mapRow(...), $rows);
    }

    /** @return list<User> */
    public function listByOrganizationId(int $organizationId): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::SELECT_COLUMNS . ' FROM users WHERE organization_id = ? ORDER BY id ASC',
            [$organizationId],
        );

        return array_map($this->mapRow(...), $rows);
    }

    public function create(
        string $email,
        string $passwordHash,
        string $role,
        ?int $organizationId = null,
        ?string $orgRole = null,
    ): User {
        $now = date('Y-m-d H:i:s');
        $this->query->execute(
            'INSERT INTO users (email, password_hash, role, organization_id, org_role, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$email, $passwordHash, $role, $organizationId, $orgRole, 'active', $now, $now],
        );

        $user = $this->findByEmail($email);

        assert($user !== null);

        return $user;
    }

    public function updateOrganization(int $id, ?int $organizationId, ?string $orgRole): void
    {
        $this->query->execute(
            'UPDATE users SET organization_id = ?, org_role = ?, updated_at = NOW() WHERE id = ?',
            [$organizationId, $orgRole, $id],
        );
    }

    public function updateRole(int $id, string $role): void
    {
        $this->query->execute(
            'UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?',
            [$role, $id],
        );
    }

    public function updateEmail(int $id, string $email): void
    {
        $this->query->execute(
            'UPDATE users SET email = ?, updated_at = NOW() WHERE id = ?',
            [$email, $id],
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
            'SELECT ' . self::SELECT_COLUMNS . ' FROM users WHERE invite_token_hash = ?',
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
            'SELECT ' . self::SELECT_COLUMNS . ' FROM users WHERE password_reset_token_hash = ?',
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

    public function storeEmailVerification(int $id, string $pendingEmail, string $tokenHash, int $expiresAt): void
    {
        $expiresAtStr = date('Y-m-d H:i:s', $expiresAt);
        $this->query->execute(
            'UPDATE users
                SET pending_email = ?, email_verification_token_hash = ?, email_verification_expires_at = ?, updated_at = NOW()
                WHERE id = ?',
            [$pendingEmail, $tokenHash, $expiresAtStr, $id],
        );
    }

    public function findByEmailVerificationToken(string $tokenHash): ?User
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::SELECT_COLUMNS . ' FROM users WHERE email_verification_token_hash = ?',
            [$tokenHash],
        );

        if ($row === null) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function applyPendingEmail(int $id): void
    {
        $this->query->execute(
            'UPDATE users
                SET email = pending_email,
                    pending_email = NULL,
                    email_verification_token_hash = NULL,
                    email_verification_expires_at = NULL,
                    updated_at = NOW()
                WHERE id = ? AND pending_email IS NOT NULL',
            [$id],
        );
    }

    public function clearEmailVerification(int $id): void
    {
        $this->query->execute(
            'UPDATE users
                SET pending_email = NULL, email_verification_token_hash = NULL, email_verification_expires_at = NULL, updated_at = NOW()
                WHERE id = ?',
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
            organizationId: isset($row['organization_id']) ? (int) $row['organization_id'] : null,
            orgRole: isset($row['org_role']) ? (string) $row['org_role'] : null,
            status: (string) ($row['status'] ?? 'active'),
            inviteTokenHash: isset($row['invite_token_hash']) ? (string) $row['invite_token_hash'] : null,
            inviteExpiresAt: isset($row['invite_expires_at']) ? (int) $row['invite_expires_at'] : null,
            passwordResetTokenHash: isset($row['password_reset_token_hash']) ? (string) $row['password_reset_token_hash'] : null,
            passwordResetExpiresAt: isset($row['password_reset_expires_at']) ? (int) $row['password_reset_expires_at'] : null,
            pendingEmail: isset($row['pending_email']) ? (string) $row['pending_email'] : null,
            emailVerificationTokenHash: isset($row['email_verification_token_hash']) ? (string) $row['email_verification_token_hash'] : null,
            emailVerificationExpiresAt: isset($row['email_verification_expires_at']) ? (int) $row['email_verification_expires_at'] : null,
            createdAt: isset($row['created_at']) ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (int) $row['updated_at'] : null,
        );
    }
}
