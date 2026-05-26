<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

final readonly class ListCommentsOutput
{
    /** @param ListCommentsItem[] $items */
    public function __construct(public array $items)
    {
    }
}
