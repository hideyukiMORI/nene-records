<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoConnectTokenRepository implements ConnectTokenRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private RequestScopedHolder $orgId,
        private ClockInterface $clock,
    ) {
    }

    /** @return list<ConnectTokenSummary> */
    public function findAllSummaries(): array
    {
        $rows = $this->query->fetchAll(
            'SELECT service, token_hint, created_at, updated_at
             FROM org_connect_tokens
             WHERE organization_id = ?
             ORDER BY service ASC',
            [$this->orgId->get()],
        );

        $summaries = [];

        foreach ($rows as $row) {
            $summary = $this->mapRow($row);

            // A row whose `service` is no longer in the enum (removed integration, hand-edited
            // database) is skipped rather than crashing the list: the operator still needs the
            // page to load in order to delete it.
            if ($summary !== null) {
                $summaries[] = $summary;
            }
        }

        return $summaries;
    }

    public function findSummary(ConnectTokenService $service): ?ConnectTokenSummary
    {
        $row = $this->query->fetchOne(
            'SELECT service, token_hint, created_at, updated_at
             FROM org_connect_tokens
             WHERE organization_id = ? AND service = ?',
            [$this->orgId->get(), $service->value],
        );

        return $row === null ? null : $this->mapRow($row);
    }

    public function findEnvelope(ConnectTokenService $service): ?string
    {
        $row = $this->query->fetchOne(
            'SELECT token_ciphertext
             FROM org_connect_tokens
             WHERE organization_id = ? AND service = ?',
            [$this->orgId->get(), $service->value],
        );

        if ($row === null) {
            return null;
        }

        $envelope = (string) $row['token_ciphertext'];

        return $envelope === '' ? null : $envelope;
    }

    public function save(ConnectTokenService $service, string $envelope, string $hint): ConnectTokenSummary
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $existing = $this->findSummary($service);

        // Upsert written as select-then-write rather than `ON DUPLICATE KEY UPDATE`, which is
        // MySQL-only: the repository tests run the shipped schema on SQLite.
        if ($existing === null) {
            $this->query->execute(
                'INSERT INTO org_connect_tokens
                     (organization_id, service, token_ciphertext, token_hint, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$this->orgId->get(), $service->value, $envelope, $hint, $now, $now],
            );

            return new ConnectTokenSummary($service, $hint, $now, $now);
        }

        $this->query->execute(
            'UPDATE org_connect_tokens
             SET token_ciphertext = ?, token_hint = ?, updated_at = ?
             WHERE organization_id = ? AND service = ?',
            [$envelope, $hint, $now, $this->orgId->get(), $service->value],
        );

        return new ConnectTokenSummary($service, $hint, $existing->createdAt, $now);
    }

    public function delete(ConnectTokenService $service): void
    {
        $this->query->execute(
            'DELETE FROM org_connect_tokens WHERE organization_id = ? AND service = ?',
            [$this->orgId->get(), $service->value],
        );
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): ?ConnectTokenSummary
    {
        $service = ConnectTokenService::tryFrom((string) $row['service']);

        if ($service === null) {
            return null;
        }

        return new ConnectTokenSummary(
            service: $service,
            hint: (string) $row['token_hint'],
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
