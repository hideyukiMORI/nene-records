<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use NeNeRecords\Auth\UserRepositoryInterface;

final readonly class ChangeEmailUseCase implements ChangeEmailUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(ChangeEmailInput $input): void
    {
        $user = $this->users->findById($input->userId);

        if ($user === null) {
            throw new UserNotFoundException($input->userId);
        }

        // No-op if email is unchanged
        if ($user->email === $input->email) {
            return;
        }

        $existing = $this->users->findByEmail($input->email);

        if ($existing !== null) {
            throw new UserEmailConflictException($input->email);
        }

        $this->users->updateEmail($input->userId, $input->email);
    }
}
