<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

use RuntimeException;

final class BlocksFieldNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Blocks field with id {$id} was not found.");
    }
}
