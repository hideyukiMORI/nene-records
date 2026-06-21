<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

interface UpdateBlocksFieldUseCaseInterface
{
    /**
     * @throws BlocksFieldNotFoundException when no blocks field matches the given id.
     */
    public function execute(UpdateBlocksFieldInput $input): UpdateBlocksFieldOutput;
}
