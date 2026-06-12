<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class WidgetRouteRegistrar
{
    public function __construct(
        private ListWidgetsHandler $listHandler,
        private ListPublicWidgetsHandler $listPublicHandler,
        private CreateWidgetHandler $createHandler,
        private UpdateWidgetHandler $updateHandler,
        private DeleteWidgetHandler $deleteHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $listPublic = $this->listPublicHandler;
        $create = $this->createHandler;
        $update = $this->updateHandler;
        $delete = $this->deleteHandler;

        $router->get('/api/v1/widgets', static fn (ServerRequestInterface $request) => $list->handle($request));
        $router->post('/api/v1/widgets', static fn (ServerRequestInterface $request) => $create->handle($request));
        $router->put('/api/v1/widgets/{id}', static fn (ServerRequestInterface $request) => $update->handle($request));
        $router->delete('/api/v1/widgets/{id}', static fn (ServerRequestInterface $request) => $delete->handle($request));
        // Public endpoint (open via ALWAYS_OPEN_PREFIXES /api/v1/public/)
        $router->get('/api/v1/public/widgets', static fn (ServerRequestInterface $request) => $listPublic->handle($request));
    }
}
