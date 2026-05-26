<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

interface DeleteWebhookUseCaseInterface
{
    public function execute(int $id): void;
}
