<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

use RuntimeException;

final class EnumFieldNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("enum field with id {$id} was not found.");
    }
}
