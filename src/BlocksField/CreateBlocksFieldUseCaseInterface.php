<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

use NeNeRecords\Entity\EntityNotFoundException;

interface CreateBlocksFieldUseCaseInterface
{
    /**
     * @throws EntityNotFoundException When {@see CreateBlocksFieldInput::$entityId} does not exist.
     */
    public function execute(CreateBlocksFieldInput $input): CreateBlocksFieldOutput;
}
