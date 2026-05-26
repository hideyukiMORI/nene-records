<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class UpdateWebhookUseCase implements UpdateWebhookUseCaseInterface
{
    public function __construct(
        private WebhookRepositoryInterface $webhooks,
    ) {
    }

    public function execute(UpdateWebhookInput $input): UpdateWebhookOutput
    {
        $existing = $this->webhooks->findById($input->id);

        if ($existing === null) {
            throw new WebhookNotFoundException($input->id);
        }

        $now = date('Y-m-d H:i:s');

        $updated = new Webhook(
            id: $input->id,
            url: $input->url,
            events: $input->events,
            entityTypeId: $input->entityTypeId,
            secret: $input->secret,
            isActive: $input->isActive,
            createdAt: $existing->createdAt,
            updatedAt: $now,
        );

        $this->webhooks->update($updated);

        return new UpdateWebhookOutput(
            id: $input->id,
            url: $input->url,
            events: $input->events,
            entityTypeId: $input->entityTypeId,
            secret: $input->secret,
            isActive: $input->isActive,
            createdAt: $existing->createdAt,
            updatedAt: $now,
        );
    }
}
