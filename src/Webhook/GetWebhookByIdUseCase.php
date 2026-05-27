<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class GetWebhookByIdUseCase implements GetWebhookByIdUseCaseInterface
{
    public function __construct(
        private WebhookRepositoryInterface $webhooks,
    ) {
    }

    public function execute(GetWebhookByIdInput $input): GetWebhookByIdOutput
    {
        $webhook = $this->webhooks->findById($input->id);

        if ($webhook === null) {
            throw new WebhookNotFoundException($input->id);
        }

        return new GetWebhookByIdOutput(webhook: $webhook);
    }
}
