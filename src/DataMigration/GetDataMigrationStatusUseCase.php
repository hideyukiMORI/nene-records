<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

final readonly class GetDataMigrationStatusUseCase implements GetDataMigrationStatusUseCaseInterface
{
    public function __construct(
        private DataMigrationRepositoryInterface $repository,
    ) {
    }

    public function execute(): GetDataMigrationStatusOutput
    {
        $counts = $this->repository->countUnassigned();
        $total  = array_sum($counts);

        return new GetDataMigrationStatusOutput(
            total: $total,
            tables: $counts,
        );
    }
}
