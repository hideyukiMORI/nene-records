<?php

declare(strict_types=1);

namespace NeNeRecords\Signup;

use Nene2\Http\SecureTokenHelper;
use NeNeRecords\Auth\UserRepositoryInterface;
use NeNeRecords\User\EmailVerificationTokenException;

/**
 * Confirms a self-serve signup email via its one-time token: marks the address
 * verified and clears the token. Token-based (no auth), so the link works before
 * the new admin has signed in on their tenant subdomain.
 */
final readonly class ConfirmEmailUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(string $token): void
    {
        $user = $this->users->findByEmailVerificationToken(SecureTokenHelper::hash($token));

        if ($user === null) {
            throw EmailVerificationTokenException::invalid();
        }

        if ($user->emailVerificationExpiresAt === null || $user->emailVerificationExpiresAt < time()) {
            $this->users->clearEmailVerification($user->id);

            throw EmailVerificationTokenException::expired();
        }

        $this->users->markEmailVerified($user->id);
    }
}
