<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

use NeNeRecords\Entity\EntityNotFoundException;

interface CreateEnumFieldUseCaseInterface
{
    /**
     * @throws EntityNotFoundException When {@see CreateEnumFieldInput::$entityId} does not exist.
     */
    public function execute(CreateEnumFieldInput $input): CreateEnumFieldOutput;
}
