<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization;

use NeNeRecords\Entitlement\EntitlementResolverInterface;
use NeNeRecords\Entitlement\Entitlements;
use NeNeRecords\Entitlement\FeatureNotEntitledException;
use NeNeRecords\Entitlement\UnlimitedEntitlementResolver;
use NeNeRecords\Organization\CreateOrganizationInput;
use NeNeRecords\Organization\CreateOrganizationUseCase;
use NeNeRecords\Organization\UpdateOrganizationInput;
use NeNeRecords\Organization\UpdateOrganizationUseCase;
use PHPUnit\Framework\TestCase;

final class UpdateOrganizationCustomDomainEntitlementTest extends TestCase
{
    public function testAllowsCustomDomainUnderUnlimitedEntitlements(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $created = (new CreateOrganizationUseCase($organizations, new RecordingDefaultContentTypeSeeder(), new RecordingDefaultSettingDefsSeeder()))->execute(
            new CreateOrganizationInput(name: 'Shop', slug: 'shop'),
        );

        $output = (new UpdateOrganizationUseCase($organizations, new UnlimitedEntitlementResolver()))->execute(
            $this->setDomainInput($created->id, 'shop.example.com'),
        );

        self::assertSame('shop.example.com', $output->customDomain);
    }

    public function testDeniesCustomDomainWhenNotEntitled(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $created = (new CreateOrganizationUseCase($organizations, new RecordingDefaultContentTypeSeeder(), new RecordingDefaultSettingDefsSeeder()))->execute(
            new CreateOrganizationInput(name: 'Shop', slug: 'shop'),
        );

        $useCase = new UpdateOrganizationUseCase($organizations, $this->denyResolver());

        $this->expectException(FeatureNotEntitledException::class);
        $useCase->execute($this->setDomainInput($created->id, 'shop.example.com'));
    }

    public function testNonCustomDomainUpdatesAreNotGated(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $created = (new CreateOrganizationUseCase($organizations, new RecordingDefaultContentTypeSeeder(), new RecordingDefaultSettingDefsSeeder()))->execute(
            new CreateOrganizationInput(name: 'Shop', slug: 'shop'),
        );

        // A name-only change must succeed even when custom domains aren't entitled.
        $output = (new UpdateOrganizationUseCase($organizations, $this->denyResolver()))->execute(
            new UpdateOrganizationInput(
                id: $created->id,
                name: 'Renamed',
                slug: null,
                plan: null,
                isActive: null,
                updateCustomDomain: false,
                customDomain: null,
            ),
        );

        self::assertSame('Renamed', $output->name);
    }

    public function testClearingCustomDomainIsNotGated(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $created = (new CreateOrganizationUseCase($organizations, new RecordingDefaultContentTypeSeeder(), new RecordingDefaultSettingDefsSeeder()))->execute(
            new CreateOrganizationInput(name: 'Shop', slug: 'shop'),
        );

        // Clearing (null) must never be blocked — downgrades keep data, only new
        // non-empty assignments are gated.
        $output = (new UpdateOrganizationUseCase($organizations, $this->denyResolver()))->execute(
            new UpdateOrganizationInput(
                id: $created->id,
                name: null,
                slug: null,
                plan: null,
                isActive: null,
                updateCustomDomain: true,
                customDomain: null,
            ),
        );

        self::assertNull($output->customDomain);
    }

    private function setDomainInput(int $id, string $domain): UpdateOrganizationInput
    {
        return new UpdateOrganizationInput(
            id: $id,
            name: null,
            slug: null,
            plan: null,
            isActive: null,
            updateCustomDomain: true,
            customDomain: $domain,
        );
    }

    private function denyResolver(): EntitlementResolverInterface
    {
        return new class () implements EntitlementResolverInterface {
            public function for(int $organizationId): Entitlements
            {
                return new Entitlements(
                    customDomainAllowed: false,
                    brandingRemovable: false,
                    maxRecords: 0,
                    maxStorageBytes: 0,
                    maxAdminUsers: 0,
                );
            }
        };
    }
}
