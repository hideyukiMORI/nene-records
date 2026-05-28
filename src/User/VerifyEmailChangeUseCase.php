<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Http\SecureTokenHelper;
use NeNeRecords\Auth\UserRepositoryInterface;

/**
 * Confirms a pending email change via its verification token (#283).
 * On success the pending address is promoted to the user's email and the token cleared.
 */
final readonly class VerifyEmailChangeUseCase implements VerifyEmailChangeUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(VerifyEmailChangeInput $input): void
    {
        $tokenHash = SecureTokenHelper::hash($input->token);
        $user = $this->users->findByEmailVerificationToken($tokenHash);

        if ($user === null || $user->pendingEmail === null) {
            throw EmailVerificationTokenException::invalid();
        }

        if ($user->emailVerificationExpiresAt === null || $user->emailVerificationExpiresAt < time()) {
            // Drop the stale token so it cannot be retried.
            $this->users->clearEmailVerification($user->id);

            throw EmailVerificationTokenException::expired();
        }

        // Guard against the pending address having been claimed by someone else meanwhile.
        $conflicting = $this->users->findByEmail($user->pendingEmail);

        if ($conflicting !== null && $conflicting->id !== $user->id) {
            $this->users->clearEmailVerification($user->id);

            throw new UserEmailConflictException($user->pendingEmail);
        }

        $this->users->applyPendingEmail($user->id);
    }
}
