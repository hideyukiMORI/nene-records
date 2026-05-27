<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

final readonly class ListNotificationChannelsUseCase implements ListNotificationChannelsUseCaseInterface
{
    public function __construct(private NotificationChannelRepositoryInterface $channels)
    {
    }

    public function execute(): ListNotificationChannelsOutput
    {
        return new ListNotificationChannelsOutput($this->channels->findAll());
    }
}
