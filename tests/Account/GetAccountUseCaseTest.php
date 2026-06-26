<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Account;

use NeNeRecords\Account\GetAccountUseCase;
use NeNeRecords\Entitlement\UnlimitedEntitlementResolver;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Organization\Organization;
use NeNeRecords\Organization\OrganizationNotFoundException;
use NeNeRecords\Tests\Organization\InMemoryOrganizationRepository;
use PHPUnit\Framework\TestCase;

final class GetAccountUseCaseTest extends TestCase
{
    public function testReturnsOrgPlanEntitlementsAndUsage(): void
    {
        $orgs = new InMemoryOrganizationRepository();
        $id = $orgs->save(new Organization(name: 'My Shop', slug: 'my-shop', plan: 'free', isActive: true));

        $entities = $this->createStub(EntityRepositoryInterface::class);
        $entities->method('countByCriteria')->willReturn(12);

        $output = (new GetAccountUseCase($orgs, new UnlimitedEntitlementResolver(), $entities))->execute($id);

        self::assertSame('my-shop', $output->slug);
        self::assertSame('My Shop', $output->name);
        self::assertSame('free', $output->plan);
        // Default (self-host) entitlement resolver = unlimited.
        self::assertTrue($output->customDomainAllowed);
        self::assertSame(PHP_INT_MAX, $output->maxRecords);
        self::assertSame(12, $output->recordsUsed);
    }

    public function testThrowsWhenOrganizationMissing(): void
    {
        $orgs = new InMemoryOrganizationRepository();
        $entities = $this->createStub(EntityRepositoryInterface::class);

        $this->expectException(OrganizationNotFoundException::class);
        (new GetAccountUseCase($orgs, new UnlimitedEntitlementResolver(), $entities))->execute(999);
    }
}
