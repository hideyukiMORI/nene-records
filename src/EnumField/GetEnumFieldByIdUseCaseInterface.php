<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

interface GetEnumFieldByIdUseCaseInterface
{
    /**
     * @throws EnumFieldNotFoundException when no int field matches the given id.
     */
    public function execute(GetEnumFieldByIdInput $input): GetEnumFieldByIdOutput;
}
