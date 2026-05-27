<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class GetWebhookByIdOutput
{
    public function __construct(
        public Webhook $webhook,
    ) {
    }
}
