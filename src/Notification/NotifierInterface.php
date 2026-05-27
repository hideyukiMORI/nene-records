<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

/**
 * Fan-out notifier: delivers a message to all active configured channels.
 * Callers MUST treat this as fire-and-forget — failures are swallowed.
 */
interface NotifierInterface
{
    public function notify(NotificationMessage $message): void;
}
