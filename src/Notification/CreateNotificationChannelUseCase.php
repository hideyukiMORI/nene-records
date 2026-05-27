<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

final readonly class CreateNotificationChannelUseCase implements CreateNotificationChannelUseCaseInterface
{
    private const VALID_TYPES = ['email', 'slack', 'discord', 'chatwork', 'webhook'];

    public function __construct(private NotificationChannelRepositoryInterface $channels)
    {
    }

    public function execute(CreateNotificationChannelInput $input): NotificationChannel
    {
        if (!in_array($input->channelType, self::VALID_TYPES, true)) {
            throw new InvalidNotificationChannelTypeException($input->channelType);
        }

        return $this->channels->create(
            $input->channelType,
            $input->label,
            $input->isEnabled,
            $input->config,
        );
    }
}
