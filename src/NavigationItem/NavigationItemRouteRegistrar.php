<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class NavigationItemRouteRegistrar
{
    public function __construct(
        private ListNavigationItemsHandler $listHandler,
        private ListPublicNavigationItemsHandler $listPublicHandler,
        private CreateNavigationItemHandler $createHandler,
        private UpdateNavigationItemHandler $updateHandler,
        private DeleteNavigationItemHandler $deleteHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $listPublic = $this->listPublicHandler;
        $create = $this->createHandler;
        $update = $this->updateHandler;
        $delete = $this->deleteHandler;

        $router->get(
            '/api/v1/navigation-items',
            static fn (ServerRequestInterface $request) => $list->handle($request),
        );
        $router->post(
            '/api/v1/navigation-items',
            static fn (ServerRequestInterface $request) => $create->handle($request),
        );
        $router->put(
            '/api/v1/navigation-items/{id}',
            static fn (ServerRequestInterface $request) => $update->handle($request),
        );
        $router->delete(
            '/api/v1/navigation-items/{id}',
            static fn (ServerRequestInterface $request) => $delete->handle($request),
        );
        // Public endpoint (no auth required via ALWAYS_OPEN_PREFIXES)
        $router->get(
            '/api/v1/public/navigation-items',
            static fn (ServerRequestInterface $request) => $listPublic->handle($request),
        );
    }
}
