<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

interface UpdateEntityUseCaseInterface
{
    /**
     * @throws EntityNotFoundException when no non-deleted entity matches the given id.
     * @throws \NeNeRecords\EntityType\EntityTypeNotFoundException when {@see UpdateEntityInput::$entityTypeId} does not exist.
     */
    public function execute(UpdateEntityInput $input): UpdateEntityOutput;
}
