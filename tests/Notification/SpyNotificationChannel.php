<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Notification;

use NeNeRecords\Notification\NotificationChannelInterface;
use NeNeRecords\Notification\NotificationMessage;

/**
 * Test double that records send() calls without performing any I/O.
 */
final class SpyNotificationChannel implements NotificationChannelInterface
{
    /** @var array<int, array{message: NotificationMessage, config: array<string,mixed>}> */
    private array $calls = [];

    /**
     * @param array<string,mixed> $config
     */
    public function send(NotificationMessage $message, array $config): void
    {
        $this->calls[] = ['message' => $message, 'config' => $config];
    }

    public function callCount(): int
    {
        return count($this->calls);
    }

    public function lastMessage(): NotificationMessage|null
    {
        $last = end($this->calls);

        return $last !== false ? $last['message'] : null;
    }
}
