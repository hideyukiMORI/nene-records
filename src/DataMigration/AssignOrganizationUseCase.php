<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeRecords\Organization\OrganizationNotFoundException;
use NeNeRecords\Organization\OrganizationRepositoryInterface;

final readonly class AssignOrganizationUseCase implements AssignOrganizationUseCaseInterface
{
    public function __construct(
        private DataMigrationRepositoryInterface $repository,
        private OrganizationRepositoryInterface $organizations,
    ) {
    }

    public function execute(AssignOrganizationInput $input): AssignOrganizationOutput
    {
        if ($input->targetOrgId <= 0) {
            throw new ValidationException([
                new ValidationError(
                    'target_org_id',
                    'target_org_id must be a positive integer.',
                    'invalid',
                ),
            ]);
        }

        $org = $this->organizations->findById($input->targetOrgId);
        if ($org === null) {
            throw new OrganizationNotFoundException($input->targetOrgId);
        }

        $migrated = $this->repository->assignAll($input->targetOrgId);
        $total    = array_sum($migrated);

        return new AssignOrganizationOutput(
            organizationId: $input->targetOrgId,
            organizationName: $org->name,
            total: $total,
            tables: $migrated,
        );
    }
}
