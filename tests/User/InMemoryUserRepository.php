<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\User;

use NeNeRecords\Auth\User;
use NeNeRecords\Auth\UserRepositoryInterface;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<int, User> */
    private array $byId = [];

    /** @var array<string, int> */
    private array $emailToId = [];

    /** @var array<string, int> */
    private array $inviteTokenToId = [];

    /** @var array<string, int> */
    private array $resetTokenToId = [];

    private int $nextId = 1;

    /** @param list<User> $seed */
    public function __construct(array $seed = [])
    {
        foreach ($seed as $user) {
            $id = $user->id;
            $this->byId[$id] = $user;
            $this->emailToId[$user->email] = $id;
            $this->nextId = max($this->nextId, $id + 1);
        }
    }

    public function findByEmail(string $email): ?User
    {
        $id = $this->emailToId[$email] ?? null;

        return $id !== null ? ($this->byId[$id] ?? null) : null;
    }

    public function findById(int $id): ?User
    {
        return $this->byId[$id] ?? null;
    }

    /** @return list<User> */
    public function list(): array
    {
        $users = array_values($this->byId);
        usort($users, static fn (User $a, User $b): int => $a->id <=> $b->id);

        return $users;
    }

    /** @return list<User> */
    public function listByOrganizationId(int $organizationId): array
    {
        $users = array_values(array_filter(
            $this->byId,
            static fn (User $u): bool => $u->organizationId === $organizationId,
        ));
        usort($users, static fn (User $a, User $b): int => $a->id <=> $b->id);

        return $users;
    }

    public function create(
        string $email,
        string $passwordHash,
        string $role,
        ?int $organizationId = null,
        ?string $orgRole = null,
    ): User {
        $id   = $this->nextId++;
        $user = new User(
            id: $id,
            email: $email,
            passwordHash: $passwordHash,
            role: $role,
            organizationId: $organizationId,
            orgRole: $orgRole,
            status: 'active',
            createdAt: time(),
            updatedAt: time(),
        );
        $this->byId[$id]           = $user;
        $this->emailToId[$email]   = $id;

        return $user;
    }

    public function updateOrganization(int $id, ?int $organizationId, ?string $orgRole): void
    {
        $user = $this->byId[$id] ?? null;

        if ($user === null) {
            return;
        }

        $this->byId[$id] = new User(
            id: $user->id,
            email: $user->email,
            passwordHash: $user->passwordHash,
            role: $user->role,
            organizationId: $organizationId,
            orgRole: $orgRole,
            status: $user->status,
            inviteTokenHash: $user->inviteTokenHash,
            inviteExpiresAt: $user->inviteExpiresAt,
            passwordResetTokenHash: $user->passwordResetTokenHash,
            passwordResetExpiresAt: $user->passwordResetExpiresAt,
            createdAt: $user->createdAt,
            updatedAt: time(),
        );
    }

    public function updateRole(int $id, string $role): void
    {
        $user = $this->byId[$id] ?? null;

        if ($user === null) {
            return;
        }

        $this->byId[$id] = new User(
            id: $user->id,
            email: $user->email,
            passwordHash: $user->passwordHash,
            role: $role,
            organizationId: $user->organizationId,
            orgRole: $user->orgRole,
            status: $user->status,
            inviteTokenHash: $user->inviteTokenHash,
            inviteExpiresAt: $user->inviteExpiresAt,
            passwordResetTokenHash: $user->passwordResetTokenHash,
            passwordResetExpiresAt: $user->passwordResetExpiresAt,
            createdAt: $user->createdAt,
            updatedAt: time(),
        );
    }

    public function updateEmail(int $id, string $email): void
    {
        $user = $this->byId[$id] ?? null;

        if ($user === null) {
            return;
        }

        unset($this->emailToId[$user->email]);
        $this->emailToId[$email] = $id;

        $this->byId[$id] = new User(
            id: $user->id,
            email: $email,
            passwordHash: $user->passwordHash,
            role: $user->role,
            organizationId: $user->organizationId,
            orgRole: $user->orgRole,
            status: $user->status,
            inviteTokenHash: $user->inviteTokenHash,
            inviteExpiresAt: $user->inviteExpiresAt,
            passwordResetTokenHash: $user->passwordResetTokenHash,
            passwordResetExpiresAt: $user->passwordResetExpiresAt,
            createdAt: $user->createdAt,
            updatedAt: time(),
        );
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $user = $this->byId[$id] ?? null;

        if ($user === null) {
            return;
        }

        $this->byId[$id] = new User(
            id: $user->id,
            email: $user->email,
            passwordHash: $passwordHash,
            role: $user->role,
            organizationId: $user->organizationId,
            orgRole: $user->orgRole,
            status: $user->status,
            inviteTokenHash: $user->inviteTokenHash,
            inviteExpiresAt: $user->inviteExpiresAt,
            passwordResetTokenHash: $user->passwordResetTokenHash,
            passwordResetExpiresAt: $user->passwordResetExpiresAt,
            createdAt: $user->createdAt,
            updatedAt: time(),
        );
    }

    public function updateStatus(int $id, string $status): void
    {
        $user = $this->byId[$id] ?? null;

        if ($user === null) {
            return;
        }

        $this->byId[$id] = new User(
            id: $user->id,
            email: $user->email,
            passwordHash: $user->passwordHash,
            role: $user->role,
            organizationId: $user->organizationId,
            orgRole: $user->orgRole,
            status: $status,
            inviteTokenHash: $user->inviteTokenHash,
            inviteExpiresAt: $user->inviteExpiresAt,
            passwordResetTokenHash: $user->passwordResetTokenHash,
            passwordResetExpiresAt: $user->passwordResetExpiresAt,
            createdAt: $user->createdAt,
            updatedAt: time(),
        );
    }

    public function storeInviteToken(int $id, string $tokenHash, int $expiresAt): void
    {
        $user = $this->byId[$id] ?? null;

        if ($user === null) {
            return;
        }

        $this->inviteTokenToId[$tokenHash] = $id;
        $this->byId[$id] = new User(
            id: $user->id,
            email: $user->email,
            passwordHash: $user->passwordHash,
            role: $user->role,
            organizationId: $user->organizationId,
            orgRole: $user->orgRole,
            status: 'invited',
            inviteTokenHash: $tokenHash,
            inviteExpiresAt: $expiresAt,
            passwordResetTokenHash: $user->passwordResetTokenHash,
            passwordResetExpiresAt: $user->passwordResetExpiresAt,
            createdAt: $user->createdAt,
            updatedAt: time(),
        );
    }

    public function findByInviteToken(string $tokenHash): ?User
    {
        $id = $this->inviteTokenToId[$tokenHash] ?? null;

        return $id !== null ? ($this->byId[$id] ?? null) : null;
    }

    public function clearInviteToken(int $id): void
    {
        $user = $this->byId[$id] ?? null;

        if ($user === null) {
            return;
        }

        if ($user->inviteTokenHash !== null) {
            unset($this->inviteTokenToId[$user->inviteTokenHash]);
        }

        $this->byId[$id] = new User(
            id: $user->id,
            email: $user->email,
            passwordHash: $user->passwordHash,
            role: $user->role,
            organizationId: $user->organizationId,
            orgRole: $user->orgRole,
            status: 'active',
            inviteTokenHash: null,
            inviteExpiresAt: null,
            passwordResetTokenHash: $user->passwordResetTokenHash,
            passwordResetExpiresAt: $user->passwordResetExpiresAt,
            createdAt: $user->createdAt,
            updatedAt: time(),
        );
    }

    public function storePasswordResetToken(int $id, string $tokenHash, int $expiresAt): void
    {
        $user = $this->byId[$id] ?? null;

        if ($user === null) {
            return;
        }

        $this->resetTokenToId[$tokenHash] = $id;
        $this->byId[$id] = new User(
            id: $user->id,
            email: $user->email,
            passwordHash: $user->passwordHash,
            role: $user->role,
            organizationId: $user->organizationId,
            orgRole: $user->orgRole,
            status: $user->status,
            inviteTokenHash: $user->inviteTokenHash,
            inviteExpiresAt: $user->inviteExpiresAt,
            passwordResetTokenHash: $tokenHash,
            passwordResetExpiresAt: $expiresAt,
            createdAt: $user->createdAt,
            updatedAt: time(),
        );
    }

    public function findByPasswordResetToken(string $tokenHash): ?User
    {
        $id = $this->resetTokenToId[$tokenHash] ?? null;

        return $id !== null ? ($this->byId[$id] ?? null) : null;
    }

    public function clearPasswordResetToken(int $id): void
    {
        $user = $this->byId[$id] ?? null;

        if ($user === null) {
            return;
        }

        if ($user->passwordResetTokenHash !== null) {
            unset($this->resetTokenToId[$user->passwordResetTokenHash]);
        }

        $this->byId[$id] = new User(
            id: $user->id,
            email: $user->email,
            passwordHash: $user->passwordHash,
            role: $user->role,
            organizationId: $user->organizationId,
            orgRole: $user->orgRole,
            status: $user->status,
            inviteTokenHash: $user->inviteTokenHash,
            inviteExpiresAt: $user->inviteExpiresAt,
            passwordResetTokenHash: null,
            passwordResetExpiresAt: null,
            createdAt: $user->createdAt,
            updatedAt: time(),
        );
    }

    public function delete(int $id): void
    {
        $user = $this->byId[$id] ?? null;

        if ($user === null) {
            return;
        }

        unset($this->emailToId[$user->email]);
        unset($this->byId[$id]);
    }

    public function countByRole(string $role): int
    {
        return count(array_filter(
            $this->byId,
            static fn (User $u): bool => $u->role === $role,
        ));
    }
}
