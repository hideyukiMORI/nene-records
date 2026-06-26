<?php

declare(strict_types=1);

namespace NeNeRecords\Account;

use NeNeRecords\Entitlement\EntitlementResolverInterface;
use NeNeRecords\Entity\EntityListCriteria;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Organization\OrganizationNotFoundException;
use NeNeRecords\Organization\OrganizationRepositoryInterface;

final readonly class GetAccountUseCase implements GetAccountUseCaseInterface
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private EntitlementResolverInterface $entitlements,
        private EntityRepositoryInterface $entities,
    ) {
    }

    public function execute(int $organizationId): GetAccountOutput
    {
        $organization = $this->organizations->findById($organizationId);

        if ($organization === null) {
            throw new OrganizationNotFoundException($organizationId);
        }

        $entitlements = $this->entitlements->for($organizationId);

        // Current usage. The entity repository is org-scoped (RequestScopedHolder),
        // so an unfiltered criteria counts only this tenant's records.
        $recordsUsed = $this->entities->countByCriteria(new EntityListCriteria());

        return new GetAccountOutput(
            slug: $organization->slug,
            name: $organization->name,
            plan: $organization->plan,
            customDomain: $organization->customDomain,
            customDomainAllowed: $entitlements->customDomainAllowed,
            maxRecords: $entitlements->maxRecords,
            maxStorageBytes: $entitlements->maxStorageBytes,
            maxAdminUsers: $entitlements->maxAdminUsers,
            recordsUsed: $recordsUsed,
        );
    }
}
