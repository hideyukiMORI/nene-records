<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

interface DeleteNotificationChannelUseCaseInterface
{
    public function execute(DeleteNotificationChannelInput $input): void;
}
