<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class DeleteWebhookInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
