<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class GetWebhookByIdUseCase implements GetWebhookByIdUseCaseInterface
{
    public function __construct(
        private WebhookRepositoryInterface $webhooks,
    ) {
    }

    public function execute(int $id): Webhook
    {
        $webhook = $this->webhooks->findById($id);

        if ($webhook === null) {
            throw new WebhookNotFoundException($id);
        }

        return $webhook;
    }
}
