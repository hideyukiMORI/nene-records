<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

use Nene2\Http\ClockInterface;
use Nene2\Http\SecureTokenHelper;
use NeNeRecords\Auth\UserRepositoryInterface;

final readonly class ConfirmPasswordResetUseCase implements ConfirmPasswordResetUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private ClockInterface $clock,
    ) {
    }

    public function execute(ConfirmPasswordResetInput $input): void
    {
        $tokenHash = SecureTokenHelper::hash($input->token);
        $user = $this->users->findByPasswordResetToken($tokenHash);

        if ($user === null || $user->passwordResetExpiresAt === null || $user->passwordResetExpiresAt < $this->clock->now()->getTimestamp()) {
            throw new InvalidPasswordResetTokenException();
        }

        $newHash = password_hash($input->newPassword, PASSWORD_BCRYPT);
        $this->users->updatePassword($user->id, $newHash);
        $this->users->clearPasswordResetToken($user->id);
    }
}
