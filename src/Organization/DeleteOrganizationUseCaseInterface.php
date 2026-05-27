<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

interface DeleteOrganizationUseCaseInterface
{
    public function execute(DeleteOrganizationInput $input): void;
}
