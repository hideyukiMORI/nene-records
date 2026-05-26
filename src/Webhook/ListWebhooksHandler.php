<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListWebhooksHandler
{
    public function __construct(
        private ListWebhooksUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $items = $this->useCase->execute();

        return $this->response->create([
            'items' => array_map(
                static fn (Webhook $webhook) => WebhookHttpMapper::toArray($webhook),
                $items,
            ),
        ]);
    }
}
