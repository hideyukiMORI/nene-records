<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

interface GetBlocksFieldByIdUseCaseInterface
{
    /**
     * @throws BlocksFieldNotFoundException when no blocks field matches the given id.
     */
    public function execute(GetBlocksFieldByIdInput $input): GetBlocksFieldByIdOutput;
}
