<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class ListWebhooksUseCase implements ListWebhooksUseCaseInterface
{
    public function __construct(
        private WebhookRepositoryInterface $webhooks,
    ) {
    }

    /** @return list<Webhook> */
    public function execute(): array
    {
        return $this->webhooks->findAll();
    }
}
