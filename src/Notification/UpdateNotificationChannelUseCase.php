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

        // Write-only capability secrets (#845): an omitted sensitive config key
        // keeps its stored value so the write-only read contract does not wipe
        // the secret on an unrelated edit; a provided one replaces it.
        $config = NotificationChannelHttpMapper::mergeConfigForUpdate(
            $channel->channelType,
            $channel->config,
            $input->config,
        );

        $this->channels->update($input->id, $input->label, $input->isEnabled, $config);
    }
}
