<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

/**
 * Outcome of a single webhook send attempt.
 */
final readonly class WebhookSendResult
{
    public function __construct(
        public bool $success,
        public ?int $statusCode = null,
        public ?string $error = null,
    ) {
    }

    public static function ok(int $statusCode): self
    {
        return new self(true, $statusCode, null);
    }

    public static function failure(string $error, ?int $statusCode = null): self
    {
        return new self(false, $statusCode, $error);
    }
}
