<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use NeNeRecords\Auth\UserRepositoryInterface;

final readonly class GetUserByIdUseCase implements GetUserByIdUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(GetUserByIdInput $input): GetUserByIdOutput
    {
        $user = $this->users->findById($input->id);

        if ($user === null) {
            throw new UserNotFoundException($input->id);
        }

        return new GetUserByIdOutput(
            id: $user->id,
            email: $user->email,
            role: $user->role,
            status: $user->status,
            createdAt: $user->createdAt,
            updatedAt: $user->updatedAt,
        );
    }
}
