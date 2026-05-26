<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

enum EntitySortOrder: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}
