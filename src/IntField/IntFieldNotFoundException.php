<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

use RuntimeException;

final class IntFieldNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Int field with id {$id} was not found.");
    }
}
