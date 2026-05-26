<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use NeNeRecords\Auth\UserRepositoryInterface;

final readonly class AdminResetPasswordUseCase implements AdminResetPasswordUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(AdminResetPasswordInput $input): void
    {
        $user = $this->users->findById($input->id);

        if ($user === null) {
            throw new UserNotFoundException($input->id);
        }

        $newHash = password_hash($input->newPassword, PASSWORD_BCRYPT);
        $this->users->updatePassword($input->id, $newHash);
    }
}
