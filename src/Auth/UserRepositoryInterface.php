<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findById(int $id): ?User;

    /** @return list<User> */
    public function list(): array;

    /** @return list<User> */
    public function listByOrganizationId(int $organizationId): array;

    public function create(
        string $email,
        string $passwordHash,
        string $role,
        ?int $organizationId = null,
        ?string $orgRole = null,
    ): User;

    public function updateOrganization(int $id, ?int $organizationId, ?string $orgRole): void;

    public function updateRole(int $id, string $role): void;

    public function updateEmail(int $id, string $email): void;

    public function updatePassword(int $id, string $passwordHash): void;

    public function updateStatus(int $id, string $status): void;

    public function storeInviteToken(int $id, string $tokenHash, int $expiresAt): void;

    public function findByInviteToken(string $tokenHash): ?User;

    public function clearInviteToken(int $id): void;

    public function storePasswordResetToken(int $id, string $tokenHash, int $expiresAt): void;

    public function findByPasswordResetToken(string $tokenHash): ?User;

    public function clearPasswordResetToken(int $id): void;

    /** Store a pending email change with its hashed verification token. */
    public function storeEmailVerification(int $id, string $pendingEmail, string $tokenHash, int $expiresAt): void;

    public function findByEmailVerificationToken(string $tokenHash): ?User;

    /** Promote pending_email to email and clear the verification token. */
    public function applyPendingEmail(int $id): void;

    public function clearEmailVerification(int $id): void;

    public function delete(int $id): void;

    public function countByRole(string $role): int;
}
