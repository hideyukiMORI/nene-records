<?php

declare(strict_types=1);

namespace NeNeRecords\Account;

/**
 * The current tenant's account snapshot: its organization identity, the
 * entitlements it gets under its plan (via the core entitlement seam — unlimited
 * on self-host, plan-based on the hosted build), and current usage.
 */
final readonly class GetAccountOutput
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $plan,
        public ?string $customDomain,
        public bool $customDomainAllowed,
        public int $maxRecords,
        public int $maxStorageBytes,
        public int $maxAdminUsers,
        public int $recordsUsed,
    ) {
    }
}
