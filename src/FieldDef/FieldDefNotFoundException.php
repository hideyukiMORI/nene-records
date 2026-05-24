<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use RuntimeException;

final class FieldDefNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Field definition with id {$id} was not found.");
    }
}
