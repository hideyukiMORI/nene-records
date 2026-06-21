<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

interface DeleteBlocksFieldUseCaseInterface
{
    /**
     * Soft-deletes the blocks field.
     *
     * @throws BlocksFieldNotFoundException when no blocks field matches the given id.
     */
    public function execute(DeleteBlocksFieldByIdInput $input): void;
}
