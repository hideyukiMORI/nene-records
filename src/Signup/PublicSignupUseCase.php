<?php

declare(strict_types=1);

namespace NeNeRecords\Signup;

use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Auth\LoginInput;
use NeNeRecords\Auth\LoginUseCase;
use NeNeRecords\Auth\Role;
use NeNeRecords\Organization\CreateOrganizationInput;
use NeNeRecords\Organization\CreateOrganizationUseCaseInterface;
use NeNeRecords\User\CreateUserInput;
use NeNeRecords\User\CreateUserUseCaseInterface;

/**
 * Public self-serve signup for the subdomain SaaS: provisions a new tenant in one
 * step — organization (active, free plan, default content types auto-seeded) plus
 * its admin user — then logs that admin straight in so the URL "register → use" is
 * a single round trip.
 *
 * Mirrors {@see \NeNeRecords\Install\InstallApplication} but for an
 * untrusted public caller: the org is always active/free, never superadmin, and
 * the slug has already been format/reservation-checked by the handler. Slug and
 * email uniqueness are enforced by the underlying use cases (which raise
 * OrganizationSlugConflictException / UserEmailConflictException → 409).
 */
final readonly class PublicSignupUseCase
{
    /**
     * @param RequestScopedHolder<int> $orgHolder
     */
    public function __construct(
        private CreateOrganizationUseCaseInterface $createOrganization,
        private CreateUserUseCaseInterface $createUser,
        private LoginUseCase $login,
        private RequestScopedHolder $orgHolder,
    ) {
    }

    public function execute(PublicSignupInput $input): PublicSignupOutput
    {
        // 1. Organization (active + free + seeded). 409 on slug conflict.
        $org = $this->createOrganization->execute(new CreateOrganizationInput(
            name: $input->organizationName,
            slug: $input->slug,
        ));

        // 2. Scope subsequent writes (and the admin's email-uniqueness check) to it.
        $this->orgHolder->set($org->id);

        // 3. Admin user. 409 on email conflict (unlikely within a brand-new org).
        $this->createUser->execute(new CreateUserInput(
            email: $input->email,
            password: $input->password,
            role: Role::Admin->value,
            organizationId: $org->id,
        ));

        // 4. Auto-login through the normal login path (same token + TTL).
        $session = $this->login->execute(new LoginInput(email: $input->email, password: $input->password));

        return new PublicSignupOutput(
            token: $session->token,
            expiresAt: $session->expiresAt,
            organizationId: $org->id,
            slug: $org->slug,
            email: $session->email,
            role: $session->role,
        );
    }
}
