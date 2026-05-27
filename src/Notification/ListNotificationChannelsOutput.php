<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

final readonly class ListNotificationChannelsOutput
{
    /** @param NotificationChannel[] $items */
    public function __construct(public array $items)
    {
    }
}
