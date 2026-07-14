<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final class WebhookHttpMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Webhook $webhook): array
    {
        // The signing secret is write-only: it is never echoed back on read
        // (defense-in-depth, #836 / #824). Callers only learn whether one is
        // configured via `has_secret`.
        return [
            'id' => $webhook->id,
            'url' => $webhook->url,
            'events' => $webhook->events,
            'entity_type_id' => $webhook->entityTypeId,
            'has_secret' => $webhook->secret !== null && $webhook->secret !== '',
            'is_active' => $webhook->isActive,
            'created_at' => $webhook->createdAt,
            'updated_at' => $webhook->updatedAt,
        ];
    }
}
