<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

enum EntitySortKey: string
{
    case Id = 'id';
    case PublishedAt = 'published_at';
    case Title = 'title';
}
