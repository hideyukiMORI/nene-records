<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Notification;

use NeNeRecords\Notification\NotificationChannel;
use NeNeRecords\Notification\NotificationChannelRepositoryInterface;

class InMemoryNotificationChannelRepository implements NotificationChannelRepositoryInterface
{
    /** @var array<int, NotificationChannel> */
    private array $channels = [];

    private int $nextId = 1;

    /** @return NotificationChannel[] */
    public function findAll(): array
    {
        return array_values($this->channels);
    }

    /** @return NotificationChannel[] */
    public function findAllEnabled(): array
    {
        return array_values(
            array_filter($this->channels, static fn (NotificationChannel $ch): bool => $ch->isEnabled),
        );
    }

    public function findById(int $id): NotificationChannel|null
    {
        return $this->channels[$id] ?? null;
    }

    /**
     * @param array<string,mixed> $config
     */
    public function create(string $channelType, string $label, bool $isEnabled, array $config): NotificationChannel
    {
        $id = $this->nextId++;
        $channel = new NotificationChannel(
            id: $id,
            organizationId: 1,
            channelType: $channelType,
            label: $label,
            isEnabled: $isEnabled,
            config: $config,
            createdAt: '2026-07-01 00:00:00',
            updatedAt: '2026-07-01 00:00:00',
        );
        $this->channels[$id] = $channel;

        return $channel;
    }

    /**
     * @param array<string,mixed> $config
     */
    public function update(int $id, string $label, bool $isEnabled, array $config): void
    {
        $old = $this->channels[$id];
        $this->channels[$id] = new NotificationChannel(
            id: $old->id,
            organizationId: $old->organizationId,
            channelType: $old->channelType,
            label: $label,
            isEnabled: $isEnabled,
            config: $config,
            createdAt: $old->createdAt,
            updatedAt: '2026-07-01 00:01:00',
        );
    }

    public function delete(int $id): void
    {
        unset($this->channels[$id]);
    }
}
