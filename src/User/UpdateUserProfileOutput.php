<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class UpdateUserProfileOutput
{
    public function __construct(
        public int $userId,
        public ?string $displayName,
        public ?string $fullName,
        public ?string $jobTitle,
    ) {
    }
}
