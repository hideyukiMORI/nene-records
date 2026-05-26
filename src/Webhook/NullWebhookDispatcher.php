<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

/**
 * No-op dispatcher used in tests and when webhooks are not configured.
 */
final readonly class NullWebhookDispatcher implements WebhookDispatcherInterface
{
    public function dispatch(string $event, int $entityTypeId, int $entityId): void
    {
        // intentionally empty
    }
}
