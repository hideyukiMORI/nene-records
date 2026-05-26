<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use NeNeRecords\Auth\UserRepositoryInterface;

final readonly class ListUsersUseCase implements ListUsersUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(ListUsersInput $input): ListUsersOutput
    {
        $users = $this->users->list();

        $items = array_map(
            static fn ($user) => new ListUserItem(
                id: $user->id,
                email: $user->email,
                role: $user->role,
                status: $user->status,
                createdAt: $user->createdAt,
                updatedAt: $user->updatedAt,
            ),
            $users,
        );

        return new ListUsersOutput(items: $items);
    }
}
