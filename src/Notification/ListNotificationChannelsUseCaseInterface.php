<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

interface ListNotificationChannelsUseCaseInterface
{
    public function execute(): ListNotificationChannelsOutput;
}
