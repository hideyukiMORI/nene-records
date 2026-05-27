<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class ListWebhooksOutput
{
    public function __construct(
        /** @var list<Webhook> */
        public array $items,
    ) {
    }
}
