<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use NeNeRecords\Auth\Role;
use NeNeRecords\Auth\UserRepositoryInterface;

final readonly class UpdateUserRoleUseCase implements UpdateUserRoleUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(UpdateUserRoleInput $input): UpdateUserRoleOutput
    {
        if (Role::tryFrom($input->role) === null) {
            throw new InvalidUserRoleException($input->role);
        }

        $user = $this->users->findById($input->id);

        if ($user === null) {
            throw new UserNotFoundException($input->id);
        }

        $this->users->updateRole($input->id, $input->role);

        return new UpdateUserRoleOutput(
            id: $user->id,
            email: $user->email,
            role: $input->role,
            status: $user->status,
        );
    }
}
