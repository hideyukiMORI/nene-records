<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class ListUsersOutput
{
    /**
     * @param list<ListUserItem> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}
