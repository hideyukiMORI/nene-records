<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

interface GetOrganizationByIdUseCaseInterface
{
    public function execute(GetOrganizationByIdInput $input): GetOrganizationByIdOutput;
}
