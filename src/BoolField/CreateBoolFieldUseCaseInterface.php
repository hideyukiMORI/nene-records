<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

use NeNeRecords\Entity\EntityNotFoundException;

interface CreateBoolFieldUseCaseInterface
{
    /**
     * @throws EntityNotFoundException When {@see CreateBoolFieldInput::$entityId} does not exist.
     */
    public function execute(CreateBoolFieldInput $input): CreateBoolFieldOutput;
}
