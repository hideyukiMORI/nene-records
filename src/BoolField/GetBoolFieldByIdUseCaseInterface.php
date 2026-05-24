<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

interface GetBoolFieldByIdUseCaseInterface
{
    /**
     * @throws BoolFieldNotFoundException when no int field matches the given id.
     */
    public function execute(GetBoolFieldByIdInput $input): GetBoolFieldByIdOutput;
}
