<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

use DomainException;

final class InvalidNotificationChannelTypeException extends DomainException
{
    public function __construct(string $type)
    {
        parent::__construct("Invalid notification channel type: '{$type}'. Allowed: email, slack, discord, chatwork, webhook.");
    }
}
