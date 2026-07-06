<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

use Nene2\Http\ClockInterface;
use Throwable;

/**
 * Asynchronous webhook dispatcher (#285).
 *
 * Instead of sending HTTP requests inline (which blocks the triggering request for up
 * to N × timeout seconds), this enqueues one durable delivery row per matching webhook.
 * A separate worker ({@see WebhookDeliveryProcessor}) performs the actual sends and retries.
 *
 * Fire-and-forget: enqueue failures never propagate to the caller.
 */
final readonly class QueueingWebhookDispatcher implements WebhookDispatcherInterface
{
    public const DEFAULT_MAX_ATTEMPTS = 5;

    public function __construct(
        private WebhookRepositoryInterface $webhooks,
        private WebhookDeliveryRepositoryInterface $deliveries,
        private ClockInterface $clock,
        private int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS,
    ) {
    }

    public function dispatch(string $event, int $entityTypeId, int $entityId): void
    {
        try {
            $matching = $this->webhooks->findActiveByEventAndEntityTypeId($event, $entityTypeId);

            if ($matching === []) {
                return;
            }

            $now = $this->clock->now();

            $payload = json_encode([
                'event' => $event,
                'entity_type_id' => $entityTypeId,
                'entity_id' => $entityId,
                'occurred_at' => $now->format('c'),
            ], JSON_THROW_ON_ERROR);

            $nowTs = $now->getTimestamp();

            foreach ($matching as $webhook) {
                if ($webhook->id === null) {
                    continue;
                }

                $this->deliveries->enqueue(
                    $webhook->id,
                    $event,
                    $entityTypeId,
                    $entityId,
                    $webhook->url,
                    $webhook->secret,
                    $payload,
                    $this->maxAttempts,
                    $nowTs,
                );
            }
        } catch (Throwable) {
            // Never let webhook enqueueing surface to the caller.
        }
    }
}
