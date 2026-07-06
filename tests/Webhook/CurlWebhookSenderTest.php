<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Webhook;

use NeNeRecords\Webhook\CurlWebhookSender;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The sender is the authoritative SSRF egress guard. Internal / non-http(s)
 * destinations are refused before any connection is attempted, and the refusal
 * is surfaced as a failure result (never swallowed as a success).
 */
final class CurlWebhookSenderTest extends TestCase
{
    /** @return list<array{string}> */
    public static function rejectedUrls(): array
    {
        return [
            ['http://169.254.169.254/latest/meta-data/'], // cloud metadata
            ['http://127.0.0.1/hook'],                    // loopback
            ['http://10.0.0.1/hook'],                     // private
            ['https://192.168.1.1/hook'],                 // private
            ['http://[::1]/hook'],                        // IPv6 loopback
            ['ftp://8.8.8.8/hook'],                       // non-http(s) scheme
        ];
    }

    #[DataProvider('rejectedUrls')]
    public function testSendRejectsInternalOrNonHttpTargets(string $url): void
    {
        $result = (new CurlWebhookSender())->send($url, 'shhh', '{"event":"entity.created"}');

        self::assertFalse($result->success);
        self::assertNull($result->statusCode);
        self::assertNotNull($result->error);
        self::assertStringContainsString('Webhook URL rejected', (string) $result->error);
    }
}
