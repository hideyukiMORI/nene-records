<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class EntityHttpMapper
{
    /** @return array<string, mixed> */
    public static function revisionToArray(EntityRevision $revision): array
    {
        return [
            'id' => $revision->id,
            'entity_id' => $revision->entityId,
            'action' => $revision->action->value,
            'status' => $revision->status,
            'previous_status' => $revision->previousStatus,
            'slug' => $revision->slug,
            'previous_slug' => $revision->previousSlug,
            'actor_user_id' => $revision->actorUserId,
            'created_at' => $revision->createdAt,
        ];
    }
}
