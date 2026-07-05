<?php

declare(strict_types=1);

namespace NeNeRecords\Install;

use LogicException;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Auth\Role;
use NeNeRecords\Organization\CreateOrganizationInput;
use NeNeRecords\Organization\CreateOrganizationUseCaseInterface;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use NeNeRecords\Organization\OrganizationSlugConflictException;
use NeNeRecords\Setting\UpdateSettingInput;
use NeNeRecords\Setting\UpdateSettingUseCaseInterface;
use NeNeRecords\User\CreateUserInput;
use NeNeRecords\User\CreateUserUseCaseInterface;
use NeNeRecords\User\UserEmailConflictException;

/**
 * First-run onboarding: turns a freshly-migrated database into a usable instance
 * by creating the initial organization (which auto-seeds its default content
 * types) and its admin user. Idempotent — re-running skips whatever already
 * exists — so it is safe to run on every deploy.
 */
final readonly class InstallApplication
{
    /**
     * @param RequestScopedHolder<int> $orgHolder
     */
    public function __construct(
        private CreateOrganizationUseCaseInterface $createOrganization,
        private OrganizationRepositoryInterface $organizations,
        private CreateUserUseCaseInterface $createUser,
        private RequestScopedHolder $orgHolder,
        private UpdateSettingUseCaseInterface $updateSetting,
    ) {
    }

    public function install(InstallConfig $config): InstallResult
    {
        [$organizationId, $organizationCreated] = $this->ensureOrganization($config);

        // Scope subsequent writes (and the admin's email-uniqueness check) to the org.
        $this->orgHolder->set($organizationId);

        $adminCreated = $this->ensureAdmin($config, $organizationId);

        // Reflect the entered name into the public `site_name` setting so the site
        // title is correct out of the box (otherwise it stays the "NeNe Records"
        // default until the user edits it in the admin). Only on fresh creation:
        // this seam runs on every deploy, so re-runs must not clobber a name the
        // user has since customized.
        if ($organizationCreated) {
            $this->updateSetting->execute(new UpdateSettingInput('site_name', $config->organizationName));
        }

        return new InstallResult(
            organizationId: $organizationId,
            organizationSlug: $config->organizationSlug,
            organizationCreated: $organizationCreated,
            adminEmail: $config->adminEmail,
            adminCreated: $adminCreated,
        );
    }

    /** @return array{0: int, 1: bool} [organizationId, created] */
    private function ensureOrganization(InstallConfig $config): array
    {
        try {
            $output = $this->createOrganization->execute(new CreateOrganizationInput(
                name: $config->organizationName,
                slug: $config->organizationSlug,
            ));

            return [$output->id, true];
        } catch (OrganizationSlugConflictException) {
            $existing = $this->organizations->findBySlug($config->organizationSlug);

            if ($existing === null || $existing->id === null) {
                throw new LogicException('Organization slug conflict but the organization could not be resolved.');
            }

            return [$existing->id, false];
        }
    }

    private function ensureAdmin(InstallConfig $config, int $organizationId): bool
    {
        try {
            $this->createUser->execute(new CreateUserInput(
                email: $config->adminEmail,
                password: $config->adminPassword,
                role: Role::Admin->value,
                organizationId: $organizationId,
            ));

            return true;
        } catch (UserEmailConflictException) {
            return false;
        }
    }
}
