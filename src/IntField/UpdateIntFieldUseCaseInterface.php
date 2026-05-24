<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

interface UpdateIntFieldUseCaseInterface
{
    /**
     * @throws IntFieldNotFoundException when no int field matches the given id.
     */
    public function execute(UpdateIntFieldInput $input): UpdateIntFieldOutput;
}
