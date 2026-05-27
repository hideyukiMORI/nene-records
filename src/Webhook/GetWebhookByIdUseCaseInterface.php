<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

interface GetWebhookByIdUseCaseInterface
{
    public function execute(GetWebhookByIdInput $input): GetWebhookByIdOutput;
}
