<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class ListWebhooksUseCase implements ListWebhooksUseCaseInterface
{
    public function __construct(
        private WebhookRepositoryInterface $webhooks,
    ) {
    }

    public function execute(): ListWebhooksOutput
    {
        return new ListWebhooksOutput(
            items: $this->webhooks->findAll(),
        );
    }
}
