<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

interface WebhookRepositoryInterface
{
    /** @return list<Webhook> */
    public function findAll(): array;

    public function findById(int $id): ?Webhook;

    /**
     * Returns active webhooks that match the given event and entity type.
     *
     * @return list<Webhook>
     */
    public function findActiveByEventAndEntityTypeId(string $event, ?int $entityTypeId): array;

    public function save(Webhook $webhook): int;

    public function update(Webhook $webhook): void;

    public function delete(int $id): void;
}
