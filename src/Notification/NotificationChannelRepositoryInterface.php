<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

interface NotificationChannelRepositoryInterface
{
    /** @return NotificationChannel[] */
    public function findAll(): array;

    /** @return NotificationChannel[] */
    public function findAllEnabled(): array;

    public function findById(int $id): NotificationChannel|null;

    /**
     * @param array<string,mixed> $config
     */
    public function create(string $channelType, string $label, bool $isEnabled, array $config): NotificationChannel;

    /**
     * @param array<string,mixed> $config
     */
    public function update(int $id, string $label, bool $isEnabled, array $config): void;

    public function delete(int $id): void;
}
