<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

interface UpdateNotificationChannelUseCaseInterface
{
    public function execute(UpdateNotificationChannelInput $input): void;
}
