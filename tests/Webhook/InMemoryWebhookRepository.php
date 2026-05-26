<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Webhook;

use NeNeRecords\Webhook\Webhook;
use NeNeRecords\Webhook\WebhookNotFoundException;
use NeNeRecords\Webhook\WebhookRepositoryInterface;

final class InMemoryWebhookRepository implements WebhookRepositoryInterface
{
    /** @var array<int, Webhook> */
    private array $webhooks = [];

    private int $nextId = 1;

    /** @return list<Webhook> */
    public function findAll(): array
    {
        return array_values($this->webhooks);
    }

    public function findById(int $id): ?Webhook
    {
        return $this->webhooks[$id] ?? null;
    }

    /** @return list<Webhook> */
    public function findActiveByEventAndEntityTypeId(string $event, ?int $entityTypeId): array
    {
        return array_values(array_filter(
            $this->webhooks,
            static function (Webhook $webhook) use ($event, $entityTypeId): bool {
                if (!$webhook->isActive) {
                    return false;
                }

                if (!in_array($event, $webhook->events, true)) {
                    return false;
                }

                return $webhook->entityTypeId === null || $webhook->entityTypeId === $entityTypeId;
            },
        ));
    }

    public function save(Webhook $webhook): int
    {
        $id = $this->nextId++;
        $now = date('Y-m-d H:i:s');
        $this->webhooks[$id] = new Webhook(
            id: $id,
            url: $webhook->url,
            events: $webhook->events,
            entityTypeId: $webhook->entityTypeId,
            secret: $webhook->secret,
            isActive: $webhook->isActive,
            createdAt: $now,
            updatedAt: $now,
        );

        return $id;
    }

    public function update(Webhook $webhook): void
    {
        $id = $webhook->id;

        if ($id === null || !isset($this->webhooks[$id])) {
            throw new WebhookNotFoundException($id ?? 0);
        }

        $this->webhooks[$id] = $webhook;
    }

    public function delete(int $id): void
    {
        if (!isset($this->webhooks[$id])) {
            throw new WebhookNotFoundException($id);
        }

        unset($this->webhooks[$id]);
    }
}
