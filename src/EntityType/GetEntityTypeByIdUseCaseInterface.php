<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

interface GetEntityTypeByIdUseCaseInterface
{
    /**
     * @throws EntityTypeNotFoundException when no entity type matches the given id.
     */
    public function execute(GetEntityTypeByIdInput $input): GetEntityTypeByIdOutput;
}
