<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

use NeNeRecords\Entity\EntityNotFoundException;

interface CreateIntFieldUseCaseInterface
{
    /**
     * @throws EntityNotFoundException When {@see CreateIntFieldInput::$entityId} does not exist.
     */
    public function execute(CreateIntFieldInput $input): CreateIntFieldOutput;
}
