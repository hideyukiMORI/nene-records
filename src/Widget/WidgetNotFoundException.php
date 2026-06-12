<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

use DomainException;

final class WidgetNotFoundException extends DomainException
{
    public function __construct(int $id)
    {
        parent::__construct("Widget #{$id} was not found.");
    }
}
