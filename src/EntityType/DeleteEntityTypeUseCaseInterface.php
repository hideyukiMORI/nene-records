<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

interface DeleteEntityTypeUseCaseInterface
{
    /**
     * @throws EntityTypeNotFoundException when no entity type matches the given id.
     */
    public function execute(DeleteEntityTypeInput $input): void;
}
