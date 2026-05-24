<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use RuntimeException;

final class EntityNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Entity with id {$id} was not found.");
    }
}
