<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

/**
 * A single notification delivery channel (e.g. Email, Slack, Discord).
 *
 * Implementations MUST NOT let exceptions propagate — failures are silent.
 */
interface NotificationChannelInterface
{
    /**
     * Send a notification. Must not throw; log or silently swallow failures.
     *
     * @param array<string,mixed> $config Channel-specific configuration
     */
    public function send(NotificationMessage $message, array $config): void;
}
