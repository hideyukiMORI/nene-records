<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

/**
 * No-op notifier — used in unit tests and when no channels are configured.
 */
final readonly class NullNotifier implements NotifierInterface
{
    public function notify(NotificationMessage $message): void
    {
        // intentionally empty
    }
}
