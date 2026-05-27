<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

interface AssignOrganizationUseCaseInterface
{
    public function execute(AssignOrganizationInput $input): AssignOrganizationOutput;
}
