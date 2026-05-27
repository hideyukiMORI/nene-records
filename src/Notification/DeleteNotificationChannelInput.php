<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

final readonly class DeleteNotificationChannelInput
{
    public function __construct(public int $id)
    {
    }
}
