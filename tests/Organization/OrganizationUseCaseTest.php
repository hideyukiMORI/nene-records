<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization;

use NeNeRecords\Organization\CreateOrganizationInput;
use NeNeRecords\Organization\CreateOrganizationUseCase;
use NeNeRecords\Organization\DeleteOrganizationInput;
use NeNeRecords\Organization\DeleteOrganizationUseCase;
use NeNeRecords\Organization\OrganizationNotFoundException;
use NeNeRecords\Organization\OrganizationSlugConflictException;
use NeNeRecords\Organization\UpdateOrganizationInput;
use NeNeRecords\Organization\UpdateOrganizationUseCase;
use PHPUnit\Framework\TestCase;

final class OrganizationUseCaseTest extends TestCase
{
    // ── CreateOrganizationUseCase ─────────────────────────────────────────

    public function testCreateOrganizationSuccessfully(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $useCase = new CreateOrganizationUseCase($organizations);

        $output = $useCase->execute(new CreateOrganizationInput(
            name: 'Test Org',
            slug: 'test-org',
            plan: 'free',
            isActive: true,
            customDomain: null,
        ));

        self::assertSame(1, $output->id);
        self::assertSame('Test Org', $output->name);
        self::assertSame('test-org', $output->slug);
        self::assertSame('free', $output->plan);
        self::assertSame(true, $output->isActive);
        self::assertSame(null, $output->customDomain);
    }

    public function testCreateOrganizationAssignsSequentialIds(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $useCase = new CreateOrganizationUseCase($organizations);

        $first = $useCase->execute(new CreateOrganizationInput(
            name: 'First Org',
            slug: 'first-org',
        ));
        $second = $useCase->execute(new CreateOrganizationInput(
            name: 'Second Org',
            slug: 'second-org',
        ));

        self::assertSame(1, $first->id);
        self::assertSame(2, $second->id);
    }

    public function testCreateOrganizationThrowsSlugConflictExceptionForDuplicateSlug(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $useCase = new CreateOrganizationUseCase($organizations);

        $useCase->execute(new CreateOrganizationInput(
            name: 'First Org',
            slug: 'duplicate-slug',
        ));

        $this->expectException(OrganizationSlugConflictException::class);

        $useCase->execute(new CreateOrganizationInput(
            name: 'Second Org',
            slug: 'duplicate-slug',
        ));
    }

    // ── UpdateOrganizationUseCase ─────────────────────────────────────────

    public function testUpdateOrganizationUpdatesName(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $created = (new CreateOrganizationUseCase($organizations))->execute(
            new CreateOrganizationInput(name: 'Original Name', slug: 'my-org'),
        );

        $output = (new UpdateOrganizationUseCase($organizations))->execute(
            new UpdateOrganizationInput(
                id: $created->id,
                name: 'Updated Name',
                slug: null,
                plan: null,
                isActive: null,
                updateCustomDomain: false,
                customDomain: null,
            ),
        );

        self::assertSame($created->id, $output->id);
        self::assertSame('Updated Name', $output->name);
        self::assertSame('my-org', $output->slug);
    }

    public function testUpdateOrganizationUpdatesSlug(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $created = (new CreateOrganizationUseCase($organizations))->execute(
            new CreateOrganizationInput(name: 'My Org', slug: 'old-slug'),
        );

        $output = (new UpdateOrganizationUseCase($organizations))->execute(
            new UpdateOrganizationInput(
                id: $created->id,
                name: null,
                slug: 'new-slug',
                plan: null,
                isActive: null,
                updateCustomDomain: false,
                customDomain: null,
            ),
        );

        self::assertSame('new-slug', $output->slug);
    }

    public function testUpdateOrganizationUpdatesPlan(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $created = (new CreateOrganizationUseCase($organizations))->execute(
            new CreateOrganizationInput(name: 'My Org', slug: 'my-org', plan: 'free'),
        );

        $output = (new UpdateOrganizationUseCase($organizations))->execute(
            new UpdateOrganizationInput(
                id: $created->id,
                name: null,
                slug: null,
                plan: 'pro',
                isActive: null,
                updateCustomDomain: false,
                customDomain: null,
            ),
        );

        self::assertSame('pro', $output->plan);
    }

    public function testUpdateOrganizationUpdatesIsActive(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $created = (new CreateOrganizationUseCase($organizations))->execute(
            new CreateOrganizationInput(name: 'My Org', slug: 'my-org', isActive: true),
        );

        $output = (new UpdateOrganizationUseCase($organizations))->execute(
            new UpdateOrganizationInput(
                id: $created->id,
                name: null,
                slug: null,
                plan: null,
                isActive: false,
                updateCustomDomain: false,
                customDomain: null,
            ),
        );

        self::assertSame(false, $output->isActive);
    }

    public function testUpdateOrganizationUpdatesCustomDomain(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $created = (new CreateOrganizationUseCase($organizations))->execute(
            new CreateOrganizationInput(name: 'My Org', slug: 'my-org'),
        );

        $output = (new UpdateOrganizationUseCase($organizations))->execute(
            new UpdateOrganizationInput(
                id: $created->id,
                name: null,
                slug: null,
                plan: null,
                isActive: null,
                updateCustomDomain: true,
                customDomain: 'custom.example.com',
            ),
        );

        self::assertSame('custom.example.com', $output->customDomain);
    }

    public function testUpdateOrganizationThrowsNotFoundExceptionWhenOrgDoesNotExist(): void
    {
        $organizations = new InMemoryOrganizationRepository();

        $this->expectException(OrganizationNotFoundException::class);

        (new UpdateOrganizationUseCase($organizations))->execute(
            new UpdateOrganizationInput(
                id: 999,
                name: 'New Name',
                slug: null,
                plan: null,
                isActive: null,
                updateCustomDomain: false,
                customDomain: null,
            ),
        );
    }

    public function testUpdateOrganizationThrowsSlugConflictExceptionWhenChangingSlugToExistingSlug(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $createUseCase = new CreateOrganizationUseCase($organizations);

        $createUseCase->execute(new CreateOrganizationInput(name: 'Org A', slug: 'org-a'));
        $createdB = $createUseCase->execute(new CreateOrganizationInput(name: 'Org B', slug: 'org-b'));

        $this->expectException(OrganizationSlugConflictException::class);

        (new UpdateOrganizationUseCase($organizations))->execute(
            new UpdateOrganizationInput(
                id: $createdB->id,
                name: null,
                slug: 'org-a',
                plan: null,
                isActive: null,
                updateCustomDomain: false,
                customDomain: null,
            ),
        );
    }

    // ── DeleteOrganizationUseCase ─────────────────────────────────────────

    public function testDeleteOrganizationSuccessfully(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $created = (new CreateOrganizationUseCase($organizations))->execute(
            new CreateOrganizationInput(name: 'My Org', slug: 'my-org'),
        );

        (new DeleteOrganizationUseCase($organizations))->execute(
            new DeleteOrganizationInput(id: $created->id),
        );

        self::assertNull($organizations->findById($created->id));
    }

    public function testDeleteOrganizationThrowsNotFoundExceptionWhenOrgDoesNotExist(): void
    {
        $organizations = new InMemoryOrganizationRepository();

        $this->expectException(OrganizationNotFoundException::class);

        (new DeleteOrganizationUseCase($organizations))->execute(
            new DeleteOrganizationInput(id: 999),
        );
    }
}
