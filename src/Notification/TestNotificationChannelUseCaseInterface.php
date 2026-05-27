<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

interface TestNotificationChannelUseCaseInterface
{
    public function execute(TestNotificationChannelInput $input): void;
}
