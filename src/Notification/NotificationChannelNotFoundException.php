<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

use DomainException;

final class NotificationChannelNotFoundException extends DomainException
{
    public function __construct(int $id)
    {
        parent::__construct("Notification channel #{$id} not found.");
    }
}
