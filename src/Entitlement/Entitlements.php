<?php

declare(strict_types=1);

namespace NeNeRecords\Entitlement;

/**
 * What an organization is allowed to do under its current plan.
 *
 * Limits are expressed as DATA (this value object), never as hard-coded constants
 * at the enforcement sites — so the same product code serves both the hosted SaaS
 * (where {@see EntitlementResolverInterface} maps `plan` → limits) and self-hosted
 * installs (where {@see UnlimitedEntitlementResolver} grants everything). Operators
 * opt INTO enforcement; the default is unlimited.
 *
 * As of the introducing change only {@see self::$customDomainAllowed} is enforced.
 * The remaining fields are the schema for future plan-based limits (record /
 * storage / user quotas, branding removal) and stay effectively unlimited until a
 * plan-based resolver and the corresponding enforcement points are added.
 */
final readonly class Entitlements
{
    public function __construct(
        public bool $customDomainAllowed,
        public bool $brandingRemovable,
        public int $maxRecords,
        public int $maxStorageBytes,
        public int $maxAdminUsers,
    ) {
    }

    /**
     * Self-host / no-billing default: every feature permitted, no caps.
     * `PHP_INT_MAX` is the "unlimited" sentinel for the numeric caps.
     */
    public static function unlimited(): self
    {
        return new self(
            customDomainAllowed: true,
            brandingRemovable: true,
            maxRecords: PHP_INT_MAX,
            maxStorageBytes: PHP_INT_MAX,
            maxAdminUsers: PHP_INT_MAX,
        );
    }
}
