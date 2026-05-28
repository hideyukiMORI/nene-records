<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

interface WebhookDeliveryRepositoryInterface
{
    /**
     * Enqueue a pending delivery. Returns the new delivery id.
     */
    public function enqueue(
        int $webhookId,
        string $event,
        int $entityTypeId,
        int $entityId,
        string $targetUrl,
        ?string $secret,
        string $payload,
        int $maxAttempts,
        int $nextAttemptAt,
    ): int;

    /**
     * Fetch pending deliveries whose next_attempt_at is due (<= $now), oldest first.
     *
     * @return list<WebhookDelivery>
     */
    public function claimDue(int $now, int $limit): array;

    public function markDelivered(int $id, ?int $responseStatus): void;

    /** Keep the delivery pending for a later retry. */
    public function reschedule(int $id, int $attempts, int $nextAttemptAt, ?string $lastError, ?int $responseStatus): void;

    /** Mark the delivery permanently failed (attempts exhausted). */
    public function markFailed(int $id, int $attempts, ?string $lastError, ?int $responseStatus): void;
}
