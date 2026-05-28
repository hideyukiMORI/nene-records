<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Webhook;

use NeNeRecords\Webhook\WebhookSenderInterface;
use NeNeRecords\Webhook\WebhookSendResult;

/**
 * Test sender with a scripted outcome. Records every call.
 */
final class FakeWebhookSender implements WebhookSenderInterface
{
    /** @var list<array{url: string, secret: ?string, payload: string}> */
    public array $calls = [];

    public function __construct(
        private WebhookSendResult $result,
    ) {
    }

    public function setResult(WebhookSendResult $result): void
    {
        $this->result = $result;
    }

    public function send(string $url, ?string $secret, string $payload): WebhookSendResult
    {
        $this->calls[] = ['url' => $url, 'secret' => $secret, 'payload' => $payload];

        return $this->result;
    }
}
