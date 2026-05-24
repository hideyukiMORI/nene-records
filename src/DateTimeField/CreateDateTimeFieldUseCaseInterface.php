<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

use NeNeRecords\Entity\EntityNotFoundException;

interface CreateDateTimeFieldUseCaseInterface
{
    /**
     * @throws EntityNotFoundException When {@see CreateDateTimeFieldInput::$entityId} does not exist.
     */
    public function execute(CreateDateTimeFieldInput $input): CreateDateTimeFieldOutput;
}
