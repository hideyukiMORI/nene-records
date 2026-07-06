<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoNotificationChannelRepository implements NotificationChannelRepositoryInterface
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

    public function findAll(): array
    {
        $rows = $this->query->fetchAll(
            'SELECT * FROM notification_channels WHERE organization_id = ? ORDER BY id ASC',
            [$this->resolveOrgId()],
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function findAllEnabled(): array
    {
        $rows = $this->query->fetchAll(
            'SELECT * FROM notification_channels WHERE organization_id = ? AND is_enabled = 1 ORDER BY id ASC',
            [$this->resolveOrgId()],
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function findById(int $id): NotificationChannel|null
    {
        $row = $this->query->fetchOne(
            'SELECT * FROM notification_channels WHERE id = ? AND organization_id = ?',
            [$id, $this->resolveOrgId()],
        );

        return $row !== null ? $this->hydrate($row) : null;
    }

    public function create(string $channelType, string $label, bool $isEnabled, array $config): NotificationChannel
    {
        $configJson = json_encode($config, JSON_THROW_ON_ERROR);
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->query->execute(
            'INSERT INTO notification_channels (organization_id, channel_type, label, is_enabled, config_json, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$this->resolveOrgId(), $channelType, $label, $isEnabled ? 1 : 0, $configJson, $now, $now],
        );

        $id = (int) $this->query->lastInsertId();

        return new NotificationChannel(
            id: $id,
            organizationId: $this->resolveOrgId(),
            channelType: $channelType,
            label: $label,
            isEnabled: $isEnabled,
            config: $config,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function update(int $id, string $label, bool $isEnabled, array $config): void
    {
        $configJson = json_encode($config, JSON_THROW_ON_ERROR);
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->query->execute(
            'UPDATE notification_channels SET label = ?, is_enabled = ?, config_json = ?, updated_at = ? WHERE id = ? AND organization_id = ?',
            [$label, $isEnabled ? 1 : 0, $configJson, $now, $id, $this->resolveOrgId()],
        );
    }

    public function delete(int $id): void
    {
        $this->query->execute(
            'DELETE FROM notification_channels WHERE id = ? AND organization_id = ?',
            [$id, $this->resolveOrgId()],
        );
    }

    /** @param array<string,mixed> $row */
    private function hydrate(array $row): NotificationChannel
    {
        /** @var array<string,mixed> $config */
        $config = json_decode((string) $row['config_json'], true, 512, JSON_THROW_ON_ERROR);

        return new NotificationChannel(
            id: (int) $row['id'],
            organizationId: (int) $row['organization_id'],
            channelType: (string) $row['channel_type'],
            label: (string) $row['label'],
            isEnabled: (bool) $row['is_enabled'],
            config: $config,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }

    private function resolveOrgId(): int
    {
        return $this->orgId->get();
    }
}
