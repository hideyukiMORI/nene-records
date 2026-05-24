<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

use RuntimeException;

final class DateTimeFieldNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("datetime field with id {$id} was not found.");
    }
}
