<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

/**
 * Reads an organization's media footprint by explicit org id (#1018).
 *
 * Deliberately not {@see \NeNeRecords\Media\MediaRepositoryInterface}: that one is
 * request-scoped to the *caller's* org, while deleting an organization is a
 * superadmin action against a different tenant — the same reason the purge in
 * {@see PdoOrganizationRepository} takes the id explicitly.
 */
interface OrgMediaInventoryReaderInterface
{
    public function forOrganization(int $organizationId): OrgMediaInventory;
}
