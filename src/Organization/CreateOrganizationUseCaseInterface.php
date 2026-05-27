<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

interface CreateOrganizationUseCaseInterface
{
    public function execute(CreateOrganizationInput $input): CreateOrganizationOutput;
}
