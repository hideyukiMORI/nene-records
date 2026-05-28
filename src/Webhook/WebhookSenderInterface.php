<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

interface WebhookSenderInterface
{
    /**
     * Performs a single HTTP delivery of the payload to the target URL.
     * Signs the body with HMAC-SHA256 when a secret is provided.
     */
    public function send(string $url, ?string $secret, string $payload): WebhookSendResult;
}
