<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

use NeNeRecords\Entity\EntityNotFoundException;

interface CreateTextFieldUseCaseInterface
{
    /**
     * @throws EntityNotFoundException When {@see CreateTextFieldInput::$entityId} does not exist.
     */
    public function execute(CreateTextFieldInput $input): CreateTextFieldOutput;
}
