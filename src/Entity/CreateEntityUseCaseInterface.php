<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

interface CreateEntityUseCaseInterface
{
    /**
     * @throws \NeNeRecords\EntityType\EntityTypeNotFoundException when {@see CreateEntityInput::$entityTypeId} does not exist.
     */
    public function execute(CreateEntityInput $input): CreateEntityOutput;
}
