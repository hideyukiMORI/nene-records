<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class GetWebhookByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
