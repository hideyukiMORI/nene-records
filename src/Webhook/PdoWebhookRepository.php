<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoWebhookRepository implements WebhookRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private readonly RequestScopedHolder $orgId,
        private ClockInterface $clock,
    ) {
    }

    /** @return list<Webhook> */
    public function findAll(): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, url, events, entity_type_id, secret, is_active, created_at, updated_at
             FROM webhooks
             WHERE organization_id = ?
             ORDER BY id ASC',
            [$this->orgId->get()],
        );

        return array_map($this->mapRow(...), $rows);
    }

    public function findById(int $id): ?Webhook
    {
        $row = $this->query->fetchOne(
            'SELECT id, url, events, entity_type_id, secret, is_active, created_at, updated_at
             FROM webhooks
             WHERE id = ? AND organization_id = ?',
            [$id, $this->orgId->get()],
        );

        return $row === null ? null : $this->mapRow($row);
    }

    /** @return list<Webhook> */
    public function findActiveByEventAndEntityTypeId(string $event, ?int $entityTypeId): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, url, events, entity_type_id, secret, is_active, created_at, updated_at
             FROM webhooks
             WHERE is_active = 1 AND organization_id = ?
             ORDER BY id ASC',
            [$this->orgId->get()],
        );

        return array_values(array_filter(
            array_map($this->mapRow(...), $rows),
            static function (Webhook $webhook) use ($event, $entityTypeId): bool {
                if (!in_array($event, $webhook->events, true)) {
                    return false;
                }

                // NULL entity_type_id = matches all types; otherwise must match
                return $webhook->entityTypeId === null || $webhook->entityTypeId === $entityTypeId;
            },
        ));
    }

    public function save(Webhook $webhook): int
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->query->execute(
            'INSERT INTO webhooks (organization_id, url, events, entity_type_id, secret, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $this->orgId->get(),
                $webhook->url,
                json_encode($webhook->events, JSON_THROW_ON_ERROR),
                $webhook->entityTypeId,
                $webhook->secret,
                $webhook->isActive ? 1 : 0,
                $now,
                $now,
            ],
        );

        return $this->query->lastInsertId();
    }

    public function update(Webhook $webhook): void
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->query->execute(
            'UPDATE webhooks
             SET url = ?, events = ?, entity_type_id = ?, secret = ?, is_active = ?, updated_at = ?
             WHERE id = ? AND organization_id = ?',
            [
                $webhook->url,
                json_encode($webhook->events, JSON_THROW_ON_ERROR),
                $webhook->entityTypeId,
                $webhook->secret,
                $webhook->isActive ? 1 : 0,
                $now,
                $webhook->id,
                $this->orgId->get(),
            ],
        );
    }

    public function delete(int $id): void
    {
        $this->query->execute('DELETE FROM webhooks WHERE id = ? AND organization_id = ?', [$id, $this->orgId->get()]);
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): Webhook
    {
        $eventsJson = (string) $row['events'];
        $events = json_decode($eventsJson, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($events)) {
            $events = [];
        }

        return new Webhook(
            id: (int) $row['id'],
            url: (string) $row['url'],
            events: array_values(array_map('strval', $events)),
            entityTypeId: $row['entity_type_id'] !== null ? (int) $row['entity_type_id'] : null,
            secret: $row['secret'] !== null ? (string) $row['secret'] : null,
            isActive: (bool) $row['is_active'],
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
