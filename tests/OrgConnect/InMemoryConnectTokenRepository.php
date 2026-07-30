<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\OrgConnect;

use NeNeRecords\OrgConnect\ConnectTokenRepositoryInterface;
use NeNeRecords\OrgConnect\ConnectTokenService;
use NeNeRecords\OrgConnect\ConnectTokenSummary;

final class InMemoryConnectTokenRepository implements ConnectTokenRepositoryInterface
{
    /** @var array<string, array{envelope: string, hint: string, created: string, updated: string}> */
    private array $rows = [];

    public function __construct(private readonly string $now = '2026-06-01 10:00:00')
    {
    }

    /** @return list<ConnectTokenSummary> */
    public function findAllSummaries(): array
    {
        $summaries = [];

        foreach ($this->rows as $service => $row) {
            $enum = ConnectTokenService::tryFrom($service);

            if ($enum !== null) {
                $summaries[] = new ConnectTokenSummary($enum, $row['hint'], $row['created'], $row['updated']);
            }
        }

        return $summaries;
    }

    public function findSummary(ConnectTokenService $service): ?ConnectTokenSummary
    {
        $row = $this->rows[$service->value] ?? null;

        return $row === null
            ? null
            : new ConnectTokenSummary($service, $row['hint'], $row['created'], $row['updated']);
    }

    public function findEnvelope(ConnectTokenService $service): ?string
    {
        return $this->rows[$service->value]['envelope'] ?? null;
    }

    public function save(ConnectTokenService $service, string $envelope, string $hint): ConnectTokenSummary
    {
        $created = $this->rows[$service->value]['created'] ?? $this->now;

        $this->rows[$service->value] = [
            'envelope' => $envelope,
            'hint'     => $hint,
            'created'  => $created,
            'updated'  => $this->now,
        ];

        return new ConnectTokenSummary($service, $hint, $created, $this->now);
    }

    public function delete(ConnectTokenService $service): void
    {
        unset($this->rows[$service->value]);
    }

    /** Test-only view of what was actually persisted, for asserting the plaintext never lands. */
    public function storedEnvelope(ConnectTokenService $service): ?string
    {
        return $this->rows[$service->value]['envelope'] ?? null;
    }
}
