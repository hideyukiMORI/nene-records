<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

use DomainException;

final class MenuNotFoundException extends DomainException
{
    public function __construct(int $id)
    {
        parent::__construct("Menu #{$id} was not found.");
    }
}
