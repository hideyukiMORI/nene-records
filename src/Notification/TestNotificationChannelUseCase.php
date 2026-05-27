<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

use NeNeRecords\Notification\Channel\ChatWorkChannel;
use NeNeRecords\Notification\Channel\DiscordChannel;
use NeNeRecords\Notification\Channel\EmailChannel;
use NeNeRecords\Notification\Channel\SlackChannel;
use NeNeRecords\Notification\Channel\WebhookChannel;

final readonly class TestNotificationChannelUseCase implements TestNotificationChannelUseCaseInterface
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

    public function execute(TestNotificationChannelInput $input): void
    {
        $channel = $this->channels->findById($input->id);
        if ($channel === null) {
            throw new NotificationChannelNotFoundException($input->id);
        }

        $testMessage = new NotificationMessage(
            event: 'test',
            title: 'NeNe Records — テスト通知',
            body: "このメッセージは通知チャンネル「{$channel->label}」のテスト送信です。",
            url: null,
        );

        $impl = match ($channel->channelType) {
            'email' => $this->emailChannel,
            'slack' => $this->slackChannel,
            'discord' => $this->discordChannel,
            'chatwork' => $this->chatWorkChannel,
            'webhook' => $this->webhookChannel,
            default => null,
        };

        $impl?->send($testMessage, $channel->config);
    }
}
