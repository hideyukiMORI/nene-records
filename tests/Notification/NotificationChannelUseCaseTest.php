<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Notification;

use NeNeRecords\Mail\MailerInterface;
use NeNeRecords\Notification\Channel\ChatWorkChannel;
use NeNeRecords\Notification\Channel\DiscordChannel;
use NeNeRecords\Notification\Channel\EmailChannel;
use NeNeRecords\Notification\Channel\SlackChannel;
use NeNeRecords\Notification\Channel\WebhookChannel;
use NeNeRecords\Notification\CompositeNotifier;
use NeNeRecords\Notification\CreateNotificationChannelInput;
use NeNeRecords\Notification\CreateNotificationChannelUseCase;
use NeNeRecords\Notification\DeleteNotificationChannelInput;
use NeNeRecords\Notification\DeleteNotificationChannelUseCase;
use NeNeRecords\Notification\InvalidNotificationChannelTypeException;
use NeNeRecords\Notification\ListNotificationChannelsUseCase;
use NeNeRecords\Notification\NotificationChannel;
use NeNeRecords\Notification\NotificationChannelNotFoundException;
use NeNeRecords\Notification\NotificationChannelRepositoryInterface;
use NeNeRecords\Notification\NotificationMessage;
use NeNeRecords\Notification\NullNotifier;
use NeNeRecords\Notification\TestNotificationChannelInput;
use NeNeRecords\Notification\TestNotificationChannelUseCase;
use NeNeRecords\Notification\UpdateNotificationChannelInput;
use NeNeRecords\Notification\UpdateNotificationChannelUseCase;
use PHPUnit\Framework\TestCase;

final class NotificationChannelUseCaseTest extends TestCase
{
    // ── ListNotificationChannelsUseCase ───────────────────────────────────────

    public function testListReturnsEmptyInitially(): void
    {
        $repo = new InMemoryNotificationChannelRepository();
        $output = (new ListNotificationChannelsUseCase($repo))->execute();

        self::assertSame([], $output->items);
    }

    public function testListReturnsCreatedChannels(): void
    {
        $repo = new InMemoryNotificationChannelRepository();
        $repo->create('email', 'Email Alert', true, ['to_address' => 'admin@example.com']);
        $repo->create('slack', 'Slack Dev', true, ['webhook_url' => 'https://hooks.slack.com/xxx']);

        $output = (new ListNotificationChannelsUseCase($repo))->execute();

        self::assertCount(2, $output->items);
    }

    // ── CreateNotificationChannelUseCase ─────────────────────────────────────

    public function testCreateEmailChannelReturnsChannel(): void
    {
        $repo = new InMemoryNotificationChannelRepository();
        $useCase = new CreateNotificationChannelUseCase($repo);

        $channel = $useCase->execute(new CreateNotificationChannelInput(
            channelType: 'email',
            label: 'Email Alert',
            isEnabled: true,
            config: ['to_address' => 'admin@example.com'],
        ));

        self::assertSame(1, $channel->id);
        self::assertSame('email', $channel->channelType);
        self::assertSame('Email Alert', $channel->label);
        self::assertTrue($channel->isEnabled);
        self::assertSame('admin@example.com', $channel->config['to_address']);
    }

    public function testCreateAllChannelTypesSucceeds(): void
    {
        $repo = new InMemoryNotificationChannelRepository();
        $useCase = new CreateNotificationChannelUseCase($repo);

        foreach (['email', 'slack', 'discord', 'chatwork', 'webhook'] as $type) {
            $channel = $useCase->execute(new CreateNotificationChannelInput(
                channelType: $type,
                label: "Test {$type}",
                isEnabled: true,
                config: [],
            ));
            self::assertSame($type, $channel->channelType);
        }
    }

    public function testCreateInvalidChannelTypeThrows(): void
    {
        $repo = new InMemoryNotificationChannelRepository();
        $useCase = new CreateNotificationChannelUseCase($repo);

        $this->expectException(InvalidNotificationChannelTypeException::class);

        $useCase->execute(new CreateNotificationChannelInput(
            channelType: 'telegram',
            label: 'Telegram',
            isEnabled: true,
            config: [],
        ));
    }

    public function testCreateDisabledChannelIsNotEnabled(): void
    {
        $repo = new InMemoryNotificationChannelRepository();
        $useCase = new CreateNotificationChannelUseCase($repo);

        $channel = $useCase->execute(new CreateNotificationChannelInput(
            channelType: 'slack',
            label: 'Slack (disabled)',
            isEnabled: false,
            config: ['webhook_url' => 'https://hooks.slack.com/xxx'],
        ));

        self::assertFalse($channel->isEnabled);
    }

    // ── UpdateNotificationChannelUseCase ─────────────────────────────────────

    public function testUpdateChangesLabelAndConfig(): void
    {
        $repo = new InMemoryNotificationChannelRepository();
        $created = $repo->create('email', 'Old Label', true, ['to_address' => 'old@example.com']);

        (new UpdateNotificationChannelUseCase($repo))->execute(new UpdateNotificationChannelInput(
            id: $created->id,
            label: 'New Label',
            isEnabled: false,
            config: ['to_address' => 'new@example.com'],
        ));

        $updated = $repo->findById($created->id);
        self::assertNotNull($updated);
        self::assertSame('New Label', $updated->label);
        self::assertFalse($updated->isEnabled);
        self::assertSame('new@example.com', $updated->config['to_address']);
    }

    public function testUpdateNonExistentChannelThrows(): void
    {
        $repo = new InMemoryNotificationChannelRepository();

        $this->expectException(NotificationChannelNotFoundException::class);

        (new UpdateNotificationChannelUseCase($repo))->execute(new UpdateNotificationChannelInput(
            id: 999,
            label: 'Ghost',
            isEnabled: true,
            config: [],
        ));
    }

    // ── DeleteNotificationChannelUseCase ─────────────────────────────────────

    public function testDeleteRemovesChannel(): void
    {
        $repo = new InMemoryNotificationChannelRepository();
        $created = $repo->create('webhook', 'My Webhook', true, ['url' => 'https://example.com']);

        (new DeleteNotificationChannelUseCase($repo))->execute(new DeleteNotificationChannelInput($created->id));

        self::assertNull($repo->findById($created->id));
        self::assertSame([], $repo->findAll());
    }

    public function testDeleteNonExistentChannelThrows(): void
    {
        $repo = new InMemoryNotificationChannelRepository();

        $this->expectException(NotificationChannelNotFoundException::class);

        (new DeleteNotificationChannelUseCase($repo))->execute(new DeleteNotificationChannelInput(42));
    }

    // ── TestNotificationChannelUseCase ───────────────────────────────────────

    public function testTestUseCaseNonExistentChannelThrows(): void
    {
        $repo = new InMemoryNotificationChannelRepository();
        $emailStub = new EmailChannel($this->createStub(MailerInterface::class));

        $useCase = new TestNotificationChannelUseCase(
            channels: $repo,
            emailChannel: $emailStub,
            slackChannel: new SlackChannel(),
            discordChannel: new DiscordChannel(),
            chatWorkChannel: new ChatWorkChannel(),
            webhookChannel: new WebhookChannel(),
        );

        $this->expectException(NotificationChannelNotFoundException::class);

        $useCase->execute(new TestNotificationChannelInput(999));
    }

    public function testTestUseCaseCompletesForExistingChannel(): void
    {
        $repo = new InMemoryNotificationChannelRepository();
        // webhook channel with no URL: send() returns immediately without I/O
        $created = $repo->create('webhook', 'Test Webhook', true, []);
        $emailStub = new EmailChannel($this->createStub(MailerInterface::class));

        $useCase = new TestNotificationChannelUseCase(
            channels: $repo,
            emailChannel: $emailStub,
            slackChannel: new SlackChannel(),
            discordChannel: new DiscordChannel(),
            chatWorkChannel: new ChatWorkChannel(),
            webhookChannel: new WebhookChannel(),
        );

        // No exception should be thrown
        $useCase->execute(new TestNotificationChannelInput($created->id));

        $this->addToAssertionCount(1);
    }

    // ── CompositeNotifier ────────────────────────────────────────────────────

    public function testCompositeNotifierUsesEnabledChannelsOnly(): void
    {
        // The tracking repo records whether findAllEnabled() was called vs findAll()
        $trackingRepo = new TrackingNotificationChannelRepository();
        $trackingRepo->create('email', 'Enabled Email', true, []);
        $trackingRepo->create('slack', 'Disabled Slack', false, []);

        $emailStub = new EmailChannel($this->createStub(MailerInterface::class));
        $notifier = new CompositeNotifier(
            channels: $trackingRepo,
            emailChannel: $emailStub,
            slackChannel: new SlackChannel(),
            discordChannel: new DiscordChannel(),
            chatWorkChannel: new ChatWorkChannel(),
            webhookChannel: new WebhookChannel(),
        );

        $notifier->notify(new NotificationMessage(
            event: 'comment.submitted',
            title: 'New comment',
            body: 'Hello world',
        ));

        self::assertTrue($trackingRepo->findAllEnabledCalled, 'findAllEnabled() should have been called');
        self::assertFalse($trackingRepo->findAllCalled, 'findAll() should NOT have been called');
    }

    public function testCompositeNotifierWithNoChannelsIsNoOp(): void
    {
        $repo = new InMemoryNotificationChannelRepository();
        $emailStub = new EmailChannel($this->createStub(MailerInterface::class));

        $notifier = new CompositeNotifier(
            channels: $repo,
            emailChannel: $emailStub,
            slackChannel: new SlackChannel(),
            discordChannel: new DiscordChannel(),
            chatWorkChannel: new ChatWorkChannel(),
            webhookChannel: new WebhookChannel(),
        );

        // Should not throw
        $notifier->notify(new NotificationMessage(
            event: 'comment.submitted',
            title: 'New comment',
            body: 'Hello world',
        ));

        $this->addToAssertionCount(1);
    }

    public function testCompositeNotifierSwallowsRepositoryException(): void
    {
        // A repo whose findAllEnabled() always throws
        $throwingRepo = new class () implements NotificationChannelRepositoryInterface {
            /** @return NotificationChannel[] */
            public function findAll(): array
            {
                return [];
            }

            /** @return NotificationChannel[] */
            public function findAllEnabled(): array
            {
                throw new \RuntimeException('DB error');
            }

            public function findById(int $id): NotificationChannel|null
            {
                return null;
            }

            /** @param array<string,mixed> $config */
            public function create(string $channelType, string $label, bool $isEnabled, array $config): NotificationChannel
            {
                throw new \RuntimeException('DB error');
            }

            /** @param array<string,mixed> $config */
            public function update(int $id, string $label, bool $isEnabled, array $config): void
            {
                throw new \RuntimeException('DB error');
            }

            public function delete(int $id): void
            {
                throw new \RuntimeException('DB error');
            }
        };

        $emailStub = new EmailChannel($this->createStub(MailerInterface::class));

        $notifier = new CompositeNotifier(
            channels: $throwingRepo,
            emailChannel: $emailStub,
            slackChannel: new SlackChannel(),
            discordChannel: new DiscordChannel(),
            chatWorkChannel: new ChatWorkChannel(),
            webhookChannel: new WebhookChannel(),
        );

        // Should NOT propagate the repository exception (fire-and-forget contract)
        $notifier->notify(new NotificationMessage(
            event: 'comment.submitted',
            title: 'New comment',
            body: 'Hello world',
        ));

        $this->addToAssertionCount(1);
    }

    // ── NullNotifier ─────────────────────────────────────────────────────────

    public function testNullNotifierDoesNothing(): void
    {
        $notifier = new NullNotifier();

        // Should not throw
        $notifier->notify(new NotificationMessage(
            event: 'comment.submitted',
            title: 'No-op',
            body: 'This should be silently dropped',
        ));

        $this->addToAssertionCount(1);
    }
}
