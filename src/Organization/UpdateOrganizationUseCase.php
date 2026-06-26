<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use NeNeRecords\Entitlement\EntitlementResolverInterface;
use NeNeRecords\Entitlement\FeatureNotEntitledException;

final readonly class UpdateOrganizationUseCase implements UpdateOrganizationUseCaseInterface
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private EntitlementResolverInterface $entitlements,
    ) {
    }

    public function execute(UpdateOrganizationInput $input): UpdateOrganizationOutput
    {
        $org = $this->organizations->findById($input->id);

        if ($org === null) {
            throw new OrganizationNotFoundException($input->id);
        }

        $name         = $input->name     ?? $org->name;
        $slug         = $input->slug     ?? $org->slug;
        $plan         = $input->plan     ?? $org->plan;
        $isActive     = $input->isActive ?? $org->isActive;
        $externalId   = $input->updateExternalId ? $input->externalId : $org->externalId;
        $customDomain = $input->updateCustomDomain ? $input->customDomain : $org->customDomain;

        // Custom domain is a plan-gated feature. Enforce only when assigning a new,
        // non-empty domain — a downgrade never breaks an org that already has one
        // (existing data is preserved; only new assignments are gated). With the
        // default UnlimitedEntitlementResolver this is a no-op.
        if (
            $input->updateCustomDomain
            && $customDomain !== null
            && $customDomain !== ''
            && $customDomain !== $org->customDomain
            && !$this->entitlements->for($input->id)->customDomainAllowed
        ) {
            throw new FeatureNotEntitledException('custom domain');
        }

        if ($slug !== $org->slug) {
            $existing = $this->organizations->findBySlug($slug);

            if ($existing !== null && $existing->id !== $input->id) {
                throw new OrganizationSlugConflictException($slug);
            }
        }

        $updated = new Organization(
            name: $name,
            slug: $slug,
            plan: $plan,
            isActive: $isActive,
            id: $input->id,
            externalId: $externalId,
            customDomain: $customDomain,
        );

        $this->organizations->update($updated);

        // Re-fetch to pick up server-generated timestamps (updated_at etc.).
        // update() throws OrganizationNotFoundException if the record is missing,
        // so findById() is guaranteed to return a non-null value here.
        $refreshed = $this->organizations->findById($input->id);

        assert($refreshed !== null);

        return new UpdateOrganizationOutput(
            id: $input->id,
            name: $refreshed->name,
            slug: $refreshed->slug,
            plan: $refreshed->plan,
            isActive: $refreshed->isActive,
            externalId: $refreshed->externalId,
            customDomain: $refreshed->customDomain,
            createdAt: $refreshed->createdAt,
            updatedAt: $refreshed->updatedAt,
        );
    }
}
