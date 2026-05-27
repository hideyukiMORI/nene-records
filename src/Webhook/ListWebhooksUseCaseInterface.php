<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

interface ListWebhooksUseCaseInterface
{
    public function execute(): ListWebhooksOutput;
}
