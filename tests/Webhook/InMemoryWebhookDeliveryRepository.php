<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Webhook;

use NeNeRecords\Webhook\WebhookDelivery;
use NeNeRecords\Webhook\WebhookDeliveryRepositoryInterface;

final class InMemoryWebhookDeliveryRepository implements WebhookDeliveryRepositoryInterface
{
    /** @var array<int, WebhookDelivery> */
    public array $rows = [];

    private int $nextId = 1;

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
    ): int {
        $id = $this->nextId++;
        $this->rows[$id] = new WebhookDelivery(
            id: $id,
            webhookId: $webhookId,
            event: $event,
            entityTypeId: $entityTypeId,
            entityId: $entityId,
            targetUrl: $targetUrl,
            secret: $secret,
            payload: $payload,
            status: 'pending',
            attempts: 0,
            maxAttempts: $maxAttempts,
            nextAttemptAt: $nextAttemptAt,
        );

        return $id;
    }

    /** @return list<WebhookDelivery> */
    public function claimDue(int $now, int $limit): array
    {
        $due = array_filter(
            $this->rows,
            static fn (WebhookDelivery $d): bool => $d->status === 'pending' && $d->nextAttemptAt <= $now,
        );
        usort($due, static fn (WebhookDelivery $a, WebhookDelivery $b): int => $a->nextAttemptAt <=> $b->nextAttemptAt);

        return array_slice($due, 0, $limit);
    }

    public function markDelivered(int $id, ?int $responseStatus): void
    {
        $this->replace($id, 'delivered', $this->rows[$id]->attempts + 1, $this->rows[$id]->nextAttemptAt, null, $responseStatus);
    }

    public function reschedule(int $id, int $attempts, int $nextAttemptAt, ?string $lastError, ?int $responseStatus): void
    {
        $this->replace($id, 'pending', $attempts, $nextAttemptAt, $lastError, $responseStatus);
    }

    public function markFailed(int $id, int $attempts, ?string $lastError, ?int $responseStatus): void
    {
        $this->replace($id, 'failed', $attempts, $this->rows[$id]->nextAttemptAt, $lastError, $responseStatus);
    }

    private function replace(int $id, string $status, int $attempts, int $nextAttemptAt, ?string $lastError, ?int $responseStatus): void
    {
        $existing = $this->rows[$id];
        $this->rows[$id] = new WebhookDelivery(
            id: $existing->id,
            webhookId: $existing->webhookId,
            event: $existing->event,
            entityTypeId: $existing->entityTypeId,
            entityId: $existing->entityId,
            targetUrl: $existing->targetUrl,
            secret: $existing->secret,
            payload: $existing->payload,
            status: $status,
            attempts: $attempts,
            maxAttempts: $existing->maxAttempts,
            nextAttemptAt: $nextAttemptAt,
            lastError: $lastError,
            responseStatus: $responseStatus,
        );
    }
}
