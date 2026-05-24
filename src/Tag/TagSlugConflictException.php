<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

use RuntimeException;

final class TagSlugConflictException extends RuntimeException
{
    public function __construct(
        public string $slug,
    ) {
        parent::__construct("A tag with slug {$slug} already exists.");
    }
}
