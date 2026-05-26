<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class DeleteUserInput
{
    public function __construct(
        public int $id,
        public string $currentUserEmail,
    ) {
    }
}
