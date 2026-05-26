<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

interface ListWebhooksUseCaseInterface
{
    /** @return list<Webhook> */
    public function execute(): array;
}
