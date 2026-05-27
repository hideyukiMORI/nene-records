<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

interface GetDataMigrationStatusUseCaseInterface
{
    public function execute(): GetDataMigrationStatusOutput;
}
