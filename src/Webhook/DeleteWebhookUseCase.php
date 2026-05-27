<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class DeleteWebhookUseCase implements DeleteWebhookUseCaseInterface
{
    public function __construct(
        private WebhookRepositoryInterface $webhooks,
    ) {
    }

    public function execute(DeleteWebhookInput $input): void
    {
        if ($this->webhooks->findById($input->id) === null) {
            throw new WebhookNotFoundException($input->id);
        }

        $this->webhooks->delete($input->id);
    }
}
