<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

final readonly class InviteUserInput
{
    public function __construct(
        public string $email,
        public string $role,
        public string $appBaseUrl,
        public ?int $organizationId = null,
        public ?string $orgRole = null,
    ) {
    }
}
