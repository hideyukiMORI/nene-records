<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

use Nene2\Http\ClockInterface;

final readonly class CreateWebhookUseCase implements CreateWebhookUseCaseInterface
{
    public function __construct(
        private WebhookRepositoryInterface $webhooks,
        private ClockInterface $clock,
    ) {
    }

    public function execute(CreateWebhookInput $input): CreateWebhookOutput
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

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
