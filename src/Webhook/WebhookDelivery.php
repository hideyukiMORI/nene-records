<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

/**
 * A queued webhook delivery (#285). Times are Unix timestamps.
 */
final readonly class WebhookDelivery
{
    public function __construct(
        public int $id,
        public int $webhookId,
        public string $event,
        public int $entityTypeId,
        public int $entityId,
        public string $targetUrl,
        public ?string $secret,
        public string $payload,
        public string $status,
        public int $attempts,
        public int $maxAttempts,
        public int $nextAttemptAt,
        public ?string $lastError = null,
        public ?int $responseStatus = null,
    ) {
    }
}
