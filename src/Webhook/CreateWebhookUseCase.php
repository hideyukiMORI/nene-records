<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class CreateWebhookUseCase implements CreateWebhookUseCaseInterface
{
    public function __construct(
        private WebhookRepositoryInterface $webhooks,
    ) {
    }

    public function execute(CreateWebhookInput $input): CreateWebhookOutput
    {
        $now = date('Y-m-d H:i:s');

        $webhook = new Webhook(
            id: null,
            url: $input->url,
            events: $input->events,
            entityTypeId: $input->entityTypeId,
            secret: $input->secret,
            isActive: $input->isActive,
            createdAt: $now,
            updatedAt: $now,
        );

        $id = $this->webhooks->save($webhook);

        return new CreateWebhookOutput(
            id: $id,
            url: $input->url,
            events: $input->events,
            entityTypeId: $input->entityTypeId,
            secret: $input->secret,
            isActive: $input->isActive,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
