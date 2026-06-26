<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use RuntimeException;

final class DuplicateEntityPermalinkException extends RuntimeException
{
    public function __construct(string $permalink)
    {
        parent::__construct("Entity permalink \"{$permalink}\" already exists in this organization.");
    }
}
