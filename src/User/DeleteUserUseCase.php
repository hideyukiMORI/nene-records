<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use NeNeRecords\Auth\UserRepositoryInterface;

final readonly class DeleteUserUseCase implements DeleteUserUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(DeleteUserInput $input): void
    {
        $user = $this->users->findById($input->id);

        if ($user === null) {
            throw new UserNotFoundException($input->id);
        }

        if ($user->email === $input->currentUserEmail) {
            throw new CannotDeleteSelfException();
        }

        if ($user->role === 'admin' && $this->users->countByRole('admin') <= 1) {
            throw new CannotDeleteLastAdminException();
        }

        $this->users->delete($input->id);
    }
}
