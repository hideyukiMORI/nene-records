<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

interface UpdateWebhookUseCaseInterface
{
    public function execute(UpdateWebhookInput $input): UpdateWebhookOutput;
}
