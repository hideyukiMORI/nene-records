<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

interface GetDateTimeFieldByIdUseCaseInterface
{
    /**
     * @throws DateTimeFieldNotFoundException when no int field matches the given id.
     */
    public function execute(GetDateTimeFieldByIdInput $input): GetDateTimeFieldByIdOutput;
}
