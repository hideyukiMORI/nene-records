<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

use DomainException;

final class NavigationItemNotFoundException extends DomainException
{
    public function __construct(int $id)
    {
        parent::__construct("Navigation item #{$id} was not found.");
    }
}
