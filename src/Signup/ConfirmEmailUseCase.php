<?php

declare(strict_types=1);

namespace NeNeRecords\Signup;

use Nene2\Http\ClockInterface;
use Nene2\Http\SecureTokenHelper;
use NeNeRecords\Auth\UserRepositoryInterface;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use NeNeRecords\User\EmailVerificationTokenException;

/**
 * Confirms a self-serve signup email via its one-time token: marks the address
 * verified and clears the token. Token-based (no auth), so the link works before
 * the new admin has signed in on their tenant subdomain.
 *
 * Returns the verified admin's tenant slug so the UI can send them on to their own
 * subdomain to sign in (the confirm link itself is served from the apex).
 */
final readonly class ConfirmEmailUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OrganizationRepositoryInterface $organizations,
        private ClockInterface $clock,
    ) {
    }

    public function execute(string $token): ?string
    {
        $user = $this->users->findByEmailVerificationToken(SecureTokenHelper::hash($token));

        if ($user === null) {
            throw EmailVerificationTokenException::invalid();
        }

        if ($user->emailVerificationExpiresAt === null || $user->emailVerificationExpiresAt < $this->clock->now()->getTimestamp()) {
            $this->users->clearEmailVerification($user->id);

            throw EmailVerificationTokenException::expired();
        }

        $this->users->markEmailVerified($user->id);

        if ($user->organizationId === null) {
            return null;
        }

        return $this->organizations->findById($user->organizationId)?->slug;
    }
}
