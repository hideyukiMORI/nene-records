<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

use RuntimeException;

final class BoolFieldNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("bool field with id {$id} was not found.");
    }
}
