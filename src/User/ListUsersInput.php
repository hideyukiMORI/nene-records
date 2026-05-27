<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class ListUsersInput
{
    public function __construct(
        /** superadmin は null を渡して全ユーザーを取得。admin/editor は自組織 ID を渡す。 */
        public ?int $organizationId = null,
    ) {
    }
}
