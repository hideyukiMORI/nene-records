<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

final readonly class DeleteNotificationChannelUseCase implements DeleteNotificationChannelUseCaseInterface
{
    public function __construct(private NotificationChannelRepositoryInterface $channels)
    {
    }

    public function execute(DeleteNotificationChannelInput $input): void
    {
        $channel = $this->channels->findById($input->id);
        if ($channel === null) {
            throw new NotificationChannelNotFoundException($input->id);
        }

        $this->channels->delete($input->id);
    }
}
