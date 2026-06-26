<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entitlement;

use NeNeRecords\Entitlement\UnlimitedEntitlementResolver;
use PHPUnit\Framework\TestCase;

final class UnlimitedEntitlementResolverTest extends TestCase
{
    public function testGrantsEverythingForAnyOrg(): void
    {
        $entitlements = (new UnlimitedEntitlementResolver())->for(123);

        self::assertTrue($entitlements->customDomainAllowed);
        self::assertTrue($entitlements->brandingRemovable);
        self::assertSame(PHP_INT_MAX, $entitlements->maxRecords);
        self::assertSame(PHP_INT_MAX, $entitlements->maxStorageBytes);
        self::assertSame(PHP_INT_MAX, $entitlements->maxAdminUsers);
    }

    public function testIsStableAcrossOrgs(): void
    {
        $resolver = new UnlimitedEntitlementResolver();

        self::assertTrue($resolver->for(1)->customDomainAllowed);
        self::assertTrue($resolver->for(999)->customDomainAllowed);
    }
}
