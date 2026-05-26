<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

use DomainException;

final class WebhookNotFoundException extends DomainException
{
    public function __construct(public readonly int $webhookId)
    {
        parent::__construct('Webhook not found: ' . $webhookId);
    }
}
