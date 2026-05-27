<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\DataMigration;

use Nene2\Validation\ValidationException;
use NeNeRecords\DataMigration\AssignOrganizationInput;
use NeNeRecords\DataMigration\AssignOrganizationUseCase;
use NeNeRecords\DataMigration\GetDataMigrationStatusUseCase;
use NeNeRecords\Organization\Organization;
use NeNeRecords\Organization\OrganizationNotFoundException;
use NeNeRecords\Tests\Organization\InMemoryOrganizationRepository;
use PHPUnit\Framework\TestCase;

final class DataMigrationUseCaseTest extends TestCase
{
    public function testGetStatusReturnsStatusWithTotalCount(): void
    {
        $repo = new InMemoryDataMigrationRepository([
            'entities' => 5,
            'text_fields' => 3,
        ]);

        $useCase = new GetDataMigrationStatusUseCase($repo);
        $output = $useCase->execute();

        self::assertSame(8, $output->total);
        self::assertSame(['entities' => 5, 'text_fields' => 3], $output->tables);
    }

    public function testGetStatusHandlesEmptyTables(): void
    {
        $repo = new InMemoryDataMigrationRepository([]);
        $useCase = new GetDataMigrationStatusUseCase($repo);

        $output = $useCase->execute();

        self::assertSame(0, $output->total);
        self::assertSame([], $output->tables);
    }

    public function testAssignOrganizationAssignsToValidOrganization(): void
    {
        $repo = new InMemoryDataMigrationRepository([
            'entities' => 4,
            'bool_fields' => 2,
        ]);

        $orgs = new InMemoryOrganizationRepository();
        $orgId = $orgs->save(new Organization(
            name: 'Acme Corp',
            slug: 'acme',
            plan: 'free',
            isActive: true,
        ));

        $useCase = new AssignOrganizationUseCase($repo, $orgs);
        $output = $useCase->execute(new AssignOrganizationInput(targetOrgId: $orgId));

        self::assertSame($orgId, $output->organizationId);
        self::assertSame('Acme Corp', $output->organizationName);
        self::assertSame(6, $output->total);
        self::assertSame(['entities' => 4, 'bool_fields' => 2], $output->tables);
    }

    public function testAssignOrganizationThrowsOrganizationNotFoundExceptionWhenOrgDoesNotExist(): void
    {
        $repo = new InMemoryDataMigrationRepository(['entities' => 1]);
        $orgs = new InMemoryOrganizationRepository();
        $useCase = new AssignOrganizationUseCase($repo, $orgs);

        $this->expectException(OrganizationNotFoundException::class);

        $useCase->execute(new AssignOrganizationInput(targetOrgId: 999));
    }

    public function testAssignOrganizationThrowsValidationExceptionWhenTargetOrgIdIsZero(): void
    {
        $repo = new InMemoryDataMigrationRepository(['entities' => 1]);
        $orgs = new InMemoryOrganizationRepository();
        $useCase = new AssignOrganizationUseCase($repo, $orgs);

        $this->expectException(ValidationException::class);

        $useCase->execute(new AssignOrganizationInput(targetOrgId: 0));
    }
}
