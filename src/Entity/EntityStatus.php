<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

enum EntityStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
    case Scheduled = 'scheduled';
}
