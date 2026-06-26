<?php

declare(strict_types=1);

namespace NeNeRecords\Entitlement;

/**
 * Single seam answering "what is organization X entitled to?".
 *
 * Enforcement sites depend on this interface, not on plan logic, so the deployment
 * decides the policy:
 *  - {@see UnlimitedEntitlementResolver} — default; self-host / no billing.
 *  - a future plan-based resolver — hosted SaaS; maps `organizations.plan` → limits.
 */
interface EntitlementResolverInterface
{
    public function for(int $organizationId): Entitlements;
}
