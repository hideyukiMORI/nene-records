<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

final readonly class UpdateNotificationChannelUseCase implements UpdateNotificationChannelUseCaseInterface
{
    public function __construct(private NotificationChannelRepositoryInterface $channels)
    {
    }

    public function execute(UpdateNotificationChannelInput $input): void
    {
        $channel = $this->channels->findById($input->id);
        if ($channel === null) {
            throw new NotificationChannelNotFoundException($input->id);
        }

        $this->channels->update($input->id, $input->label, $input->isEnabled, $input->config);
    }
}
