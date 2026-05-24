<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

interface UpdateDateTimeFieldUseCaseInterface
{
    /**
     * @throws DateTimeFieldNotFoundException when no int field matches the given id.
     */
    public function execute(UpdateDateTimeFieldInput $input): UpdateDateTimeFieldOutput;
}
