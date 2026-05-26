<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use NeNeRecords\Auth\UserRepositoryInterface;

final readonly class ChangeOwnPasswordUseCase implements ChangeOwnPasswordUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(ChangeOwnPasswordInput $input): void
    {
        $user = $this->users->findByEmail($input->currentUserEmail);

        if ($user === null) {
            throw new UserNotFoundException(0);
        }

        if (!password_verify($input->currentPassword, $user->passwordHash)) {
            throw new InvalidCurrentPasswordException();
        }

        $newHash = password_hash($input->newPassword, PASSWORD_BCRYPT);
        $this->users->updatePassword($user->id, $newHash);
    }
}
