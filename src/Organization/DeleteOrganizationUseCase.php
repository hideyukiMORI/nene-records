<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

final readonly class DeleteOrganizationUseCase implements DeleteOrganizationUseCaseInterface
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
    ) {
    }

    public function execute(DeleteOrganizationInput $input): void
    {
        $org = $this->organizations->findById($input->id);

        if ($org === null) {
            throw new OrganizationNotFoundException($input->id);
        }

        $this->organizations->delete($input->id);
    }
}
