<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Notification;

use NeNeRecords\Notification\NotificationChannel;
use NeNeRecords\Notification\NotificationChannelHttpMapper;
use PHPUnit\Framework\TestCase;

final class NotificationChannelHttpMapperTest extends TestCase
{
    public function testSensitiveKeysPerChannelType(): void
    {
        self::assertSame([], NotificationChannelHttpMapper::sensitiveKeys('email'));
        self::assertSame(['webhook_url'], NotificationChannelHttpMapper::sensitiveKeys('slack'));
        self::assertSame(['webhook_url'], NotificationChannelHttpMapper::sensitiveKeys('discord'));
        self::assertSame(['api_token'], NotificationChannelHttpMapper::sensitiveKeys('chatwork'));
        self::assertSame(['url', 'headers_json'], NotificationChannelHttpMapper::sensitiveKeys('webhook'));
        self::assertSame([], NotificationChannelHttpMapper::sensitiveKeys('unknown'));
    }

    public function testRedactStripsSecretsAndAddsHasFlags(): void
    {
        $redacted = NotificationChannelHttpMapper::redactConfig('webhook', [
            'url' => 'https://example.com/hook',
            'headers_json' => '{"Authorization":"Bearer x"}',
        ]);

        self::assertArrayNotHasKey('url', $redacted);
        self::assertArrayNotHasKey('headers_json', $redacted);
        self::assertTrue($redacted['has_url']);
        self::assertTrue($redacted['has_headers_json']);
    }

    public function testRedactKeepsNonSensitiveKeys(): void
    {
        $redacted = NotificationChannelHttpMapper::redactConfig('chatwork', [
            'api_token' => 'tok',
            'room_id' => '42',
        ]);

        self::assertArrayNotHasKey('api_token', $redacted);
        self::assertTrue($redacted['has_api_token']);
        self::assertSame('42', $redacted['room_id']);
    }

    public function testRedactReportsMissingAndEmptySecretsAsAbsent(): void
    {
        self::assertFalse(NotificationChannelHttpMapper::redactConfig('slack', [])['has_webhook_url']);
        self::assertFalse(NotificationChannelHttpMapper::redactConfig('slack', ['webhook_url' => ''])['has_webhook_url']);
        self::assertFalse(NotificationChannelHttpMapper::redactConfig('slack', ['webhook_url' => null])['has_webhook_url']);
    }

    public function testToArrayRedactsConfig(): void
    {
        $channel = new NotificationChannel(
            id: 7,
            organizationId: 1,
            channelType: 'slack',
            label: 'Slack',
            isEnabled: true,
            config: ['webhook_url' => 'https://hooks.slack.com/secret'],
            createdAt: '2026-07-01 00:00:00',
            updatedAt: '2026-07-01 00:00:00',
        );

        $array = NotificationChannelHttpMapper::toArray($channel);

        self::assertSame(7, $array['id']);
        self::assertSame('slack', $array['channel_type']);
        self::assertIsArray($array['config']);
        self::assertArrayNotHasKey('webhook_url', $array['config']);
        self::assertTrue($array['config']['has_webhook_url']);
    }

    public function testMergeKeepsExistingSecretWhenOmitted(): void
    {
        $merged = NotificationChannelHttpMapper::mergeConfigForUpdate(
            'slack',
            ['webhook_url' => 'https://hooks.slack.com/keep'],
            [],
        );

        self::assertSame('https://hooks.slack.com/keep', $merged['webhook_url']);
    }

    public function testMergeReplacesSecretWhenProvided(): void
    {
        $merged = NotificationChannelHttpMapper::mergeConfigForUpdate(
            'slack',
            ['webhook_url' => 'https://hooks.slack.com/old'],
            ['webhook_url' => 'https://hooks.slack.com/new'],
        );

        self::assertSame('https://hooks.slack.com/new', $merged['webhook_url']);
    }

    public function testMergeTreatsEmptySecretAsOmitted(): void
    {
        $merged = NotificationChannelHttpMapper::mergeConfigForUpdate(
            'slack',
            ['webhook_url' => 'https://hooks.slack.com/keep'],
            ['webhook_url' => ''],
        );

        self::assertSame('https://hooks.slack.com/keep', $merged['webhook_url']);
    }

    public function testMergeDropsEchoedHasFlag(): void
    {
        $merged = NotificationChannelHttpMapper::mergeConfigForUpdate(
            'slack',
            ['webhook_url' => 'https://hooks.slack.com/keep'],
            ['has_webhook_url' => true],
        );

        self::assertArrayNotHasKey('has_webhook_url', $merged);
        self::assertSame('https://hooks.slack.com/keep', $merged['webhook_url']);
    }

    public function testMergeReplacesNonSensitiveKeysWholesale(): void
    {
        $merged = NotificationChannelHttpMapper::mergeConfigForUpdate(
            'chatwork',
            ['api_token' => 'tok', 'room_id' => '1'],
            ['room_id' => '2'],
        );

        // Non-sensitive keys come from the incoming payload; the omitted token
        // is preserved from the stored config.
        self::assertSame('2', $merged['room_id']);
        self::assertSame('tok', $merged['api_token']);
    }
}
