<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

interface GetIntFieldByIdUseCaseInterface
{
    /**
     * @throws IntFieldNotFoundException when no int field matches the given id.
     */
    public function execute(GetIntFieldByIdInput $input): GetIntFieldByIdOutput;
}
