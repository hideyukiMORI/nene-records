<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class CreateWebhookInput
{
    /** @param list<string> $events */
    public function __construct(
        public string $url,
        public array $events,
        public ?int $entityTypeId,
        public ?string $secret,
        public bool $isActive,
    ) {
    }
}
