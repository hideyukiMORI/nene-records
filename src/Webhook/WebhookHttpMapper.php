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
        return [
            'id' => $webhook->id,
            'url' => $webhook->url,
            'events' => $webhook->events,
            'entity_type_id' => $webhook->entityTypeId,
            'secret' => $webhook->secret,
            'is_active' => $webhook->isActive,
            'created_at' => $webhook->createdAt,
            'updated_at' => $webhook->updatedAt,
        ];
    }
}
