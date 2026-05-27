<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

interface UpdateOrganizationUseCaseInterface
{
    public function execute(UpdateOrganizationInput $input): UpdateOrganizationOutput;
}
