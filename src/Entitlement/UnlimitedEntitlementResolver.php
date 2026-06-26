<?php

declare(strict_types=1);

namespace NeNeRecords\Entitlement;

/**
 * Grants every organization unlimited entitlements.
 *
 * This is the default binding, so a fresh `git clone` / self-hosted install (and
 * the current hosted SaaS, until a plan-based resolver is wired) is never subject
 * to commercial limits — it's the operator's own instance. Billing/limits are
 * opt-in by swapping this for a plan-based resolver.
 */
final readonly class UnlimitedEntitlementResolver implements EntitlementResolverInterface
{
    public function for(int $organizationId): Entitlements
    {
        return Entitlements::unlimited();
    }
}
