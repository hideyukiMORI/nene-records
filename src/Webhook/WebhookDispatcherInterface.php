<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

interface WebhookDispatcherInterface
{
    /**
     * Fire-and-forget: dispatch a webhook event to all matching active webhooks.
     * Failures must NOT propagate to the caller.
     */
    public function dispatch(string $event, int $entityTypeId, int $entityId): void;
}
