<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

use NeNeRecords\Notification\Channel\ChatWorkChannel;
use NeNeRecords\Notification\Channel\DiscordChannel;
use NeNeRecords\Notification\Channel\EmailChannel;
use NeNeRecords\Notification\Channel\SlackChannel;
use NeNeRecords\Notification\Channel\WebhookChannel;
use Throwable;

/**
 * Loads all enabled channels from the repository and fan-outs the message.
 * Every delivery is fire-and-forget; failures never surface to the caller.
 */
final readonly class CompositeNotifier implements NotifierInterface
{
    public function __construct(
        private NotificationChannelRepositoryInterface $channels,
        private EmailChannel $emailChannel,
        private SlackChannel $slackChannel,
        private DiscordChannel $discordChannel,
        private ChatWorkChannel $chatWorkChannel,
        private WebhookChannel $webhookChannel,
    ) {
    }

    public function notify(NotificationMessage $message): void
    {
        try {
            $channels = $this->channels->findAllEnabled();
        } catch (Throwable) {
            return;
        }

        foreach ($channels as $channel) {
            $this->deliver($channel, $message);
        }
    }

    private function deliver(NotificationChannel $channel, NotificationMessage $message): void
    {
        try {
            $impl = match ($channel->channelType) {
                'email' => $this->emailChannel,
                'slack' => $this->slackChannel,
                'discord' => $this->discordChannel,
                'chatwork' => $this->chatWorkChannel,
                'webhook' => $this->webhookChannel,
                default => null,
            };

            $impl?->send($message, $channel->config);
        } catch (Throwable) {
            // never propagate
        }
    }
}
