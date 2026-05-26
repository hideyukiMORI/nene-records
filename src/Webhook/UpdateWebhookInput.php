<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class UpdateWebhookInput
{
    /** @param list<string> $events */
    public function __construct(
        public int $id,
        public string $url,
        public array $events,
        public ?int $entityTypeId,
        public ?string $secret,
        public bool $isActive,
    ) {
    }
}
