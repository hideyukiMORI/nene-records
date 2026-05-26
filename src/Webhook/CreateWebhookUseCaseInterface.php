<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

interface CreateWebhookUseCaseInterface
{
    public function execute(CreateWebhookInput $input): CreateWebhookOutput;
}
