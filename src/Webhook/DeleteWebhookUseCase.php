<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class DeleteWebhookUseCase implements DeleteWebhookUseCaseInterface
{
    public function __construct(
        private WebhookRepositoryInterface $webhooks,
    ) {
    }

    public function execute(int $id): void
    {
        if ($this->webhooks->findById($id) === null) {
            throw new WebhookNotFoundException($id);
        }

        $this->webhooks->delete($id);
    }
}
