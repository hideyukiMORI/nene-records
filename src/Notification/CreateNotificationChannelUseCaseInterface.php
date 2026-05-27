<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

interface CreateNotificationChannelUseCaseInterface
{
    public function execute(CreateNotificationChannelInput $input): NotificationChannel;
}
