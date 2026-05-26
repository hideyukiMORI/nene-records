<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use NeNeRecords\Auth\Role;
use NeNeRecords\Auth\UserRepositoryInterface;

final readonly class CreateUserUseCase implements CreateUserUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(CreateUserInput $input): CreateUserOutput
    {
        if (Role::tryFrom($input->role) === null) {
            throw new InvalidUserRoleException($input->role);
        }

        $existing = $this->users->findByEmail($input->email);

        if ($existing !== null) {
            throw new UserEmailConflictException($input->email);
        }

        $passwordHash = password_hash($input->password, PASSWORD_BCRYPT);
        $user = $this->users->create($input->email, $passwordHash, $input->role);

        return new CreateUserOutput(
            id: $user->id,
            email: $user->email,
            role: $user->role,
            status: $user->status,
            createdAt: $user->createdAt,
        );
    }
}
