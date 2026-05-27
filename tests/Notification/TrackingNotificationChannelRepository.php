<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Notification;

use NeNeRecords\Notification\NotificationChannel;

/**
 * Extends InMemoryNotificationChannelRepository and records which
 * methods are called, so tests can verify the correct method is used.
 */
final class TrackingNotificationChannelRepository extends InMemoryNotificationChannelRepository
{
    public bool $findAllEnabledCalled = false;
    public bool $findAllCalled = false;

    /** @return NotificationChannel[] */
    public function findAllEnabled(): array
    {
        $this->findAllEnabledCalled = true;

        return parent::findAllEnabled();
    }

    /** @return NotificationChannel[] */
    public function findAll(): array
    {
        $this->findAllCalled = true;

        return parent::findAll();
    }
}
