<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Database-backed webhook delivery queue (#285).
 *
 * Intentionally NOT organization-scoped: the worker runs outside any request scope,
 * and each row already snapshots its own target URL/secret at enqueue time.
 */
final readonly class PdoWebhookDeliveryRepository implements WebhookDeliveryRepositoryInterface
{
    private const SELECT_COLUMNS = '
        id, webhook_id, event, entity_type_id, entity_id, target_url, secret, payload,
        status, attempts, max_attempts,
        UNIX_TIMESTAMP(next_attempt_at) AS next_attempt_at,
        last_error, response_status
    ';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

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
        $now = date('Y-m-d H:i:s');

        return $this->query->insert(
            'INSERT INTO webhook_deliveries
                (webhook_id, event, entity_type_id, entity_id, target_url, secret, payload,
                 status, attempts, max_attempts, next_attempt_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, \'pending\', 0, ?, ?, ?, ?)',
            [
                $webhookId,
                $event,
                $entityTypeId,
                $entityId,
                $targetUrl,
                $secret,
                $payload,
                $maxAttempts,
                date('Y-m-d H:i:s', $nextAttemptAt),
                $now,
                $now,
            ],
        );
    }

    /** @return list<WebhookDelivery> */
    public function claimDue(int $now, int $limit): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::SELECT_COLUMNS . '
                FROM webhook_deliveries
                WHERE status = \'pending\' AND next_attempt_at <= ?
                ORDER BY next_attempt_at ASC, id ASC
                LIMIT ' . max(1, $limit),
            [date('Y-m-d H:i:s', $now)],
        );

        return array_map($this->mapRow(...), $rows);
    }

    public function markDelivered(int $id, ?int $responseStatus): void
    {
        $this->query->execute(
            'UPDATE webhook_deliveries
                SET status = \'delivered\', attempts = attempts + 1, response_status = ?,
                    last_error = NULL, delivered_at = NOW(), updated_at = NOW()
                WHERE id = ?',
            [$responseStatus, $id],
        );
    }

    public function reschedule(int $id, int $attempts, int $nextAttemptAt, ?string $lastError, ?int $responseStatus): void
    {
        $this->query->execute(
            'UPDATE webhook_deliveries
                SET attempts = ?, next_attempt_at = ?, last_error = ?, response_status = ?, updated_at = NOW()
                WHERE id = ?',
            [$attempts, date('Y-m-d H:i:s', $nextAttemptAt), $lastError, $responseStatus, $id],
        );
    }

    public function markFailed(int $id, int $attempts, ?string $lastError, ?int $responseStatus): void
    {
        $this->query->execute(
            'UPDATE webhook_deliveries
                SET status = \'failed\', attempts = ?, last_error = ?, response_status = ?, updated_at = NOW()
                WHERE id = ?',
            [$attempts, $lastError, $responseStatus, $id],
        );
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): WebhookDelivery
    {
        return new WebhookDelivery(
            id: (int) $row['id'],
            webhookId: (int) $row['webhook_id'],
            event: (string) $row['event'],
            entityTypeId: (int) $row['entity_type_id'],
            entityId: (int) $row['entity_id'],
            targetUrl: (string) $row['target_url'],
            secret: isset($row['secret']) ? (string) $row['secret'] : null,
            payload: (string) $row['payload'],
            status: (string) $row['status'],
            attempts: (int) $row['attempts'],
            maxAttempts: (int) $row['max_attempts'],
            nextAttemptAt: (int) $row['next_attempt_at'],
            lastError: isset($row['last_error']) ? (string) $row['last_error'] : null,
            responseStatus: isset($row['response_status']) ? (int) $row['response_status'] : null,
        );
    }
}
