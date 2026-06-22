<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class WebhookRouteRegistrar
{
    public function __construct(
        private ListWebhooksHandler $listHandler,
        private GetWebhookByIdHandler $getByIdHandler,
        private CreateWebhookHandler $createHandler,
        private UpdateWebhookHandler $updateHandler,
        private DeleteWebhookHandler $deleteHandler,
        private ProcessWebhookDeliveriesHandler $processDeliveriesHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $getById = $this->getByIdHandler;
        $create = $this->createHandler;
        $update = $this->updateHandler;
        $delete = $this->deleteHandler;
        $processDeliveries = $this->processDeliveriesHandler;

        $router->get(
            '/api/v1/webhooks',
            static fn (ServerRequestInterface $request) => $list->handle($request),
        );
        $router->post(
            '/api/v1/webhooks',
            static fn (ServerRequestInterface $request) => $create->handle($request),
        );
        // Cron-triggered queue drain — registered before the {id} routes so the
        // literal path is never shadowed. Public (see AdminApiAuthMiddleware).
        $router->post(
            '/api/v1/webhooks/process-deliveries',
            static fn (ServerRequestInterface $request) => $processDeliveries->handle($request),
        );
        $router->get(
            '/api/v1/webhooks/{id}',
            static fn (ServerRequestInterface $request) => $getById->handle($request),
        );
        $router->put(
            '/api/v1/webhooks/{id}',
            static fn (ServerRequestInterface $request) => $update->handle($request),
        );
        $router->delete(
            '/api/v1/webhooks/{id}',
            static fn (ServerRequestInterface $request) => $delete->handle($request),
        );
    }
}
