<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

interface ListOrganizationsUseCaseInterface
{
    public function execute(ListOrganizationsInput $input): ListOrganizationsOutput;
}
