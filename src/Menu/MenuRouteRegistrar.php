<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class MenuRouteRegistrar
{
    public function __construct(
        private ListMenusHandler $listHandler,
        private ListPublicMenusHandler $listPublicHandler,
        private CreateMenuHandler $createHandler,
        private UpdateMenuHandler $updateHandler,
        private DeleteMenuHandler $deleteHandler,
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
            '/api/v1/menus',
            static fn (ServerRequestInterface $request) => $list->handle($request),
        );
        $router->post(
            '/api/v1/menus',
            static fn (ServerRequestInterface $request) => $create->handle($request),
        );
        $router->put(
            '/api/v1/menus/{id}',
            static fn (ServerRequestInterface $request) => $update->handle($request),
        );
        $router->delete(
            '/api/v1/menus/{id}',
            static fn (ServerRequestInterface $request) => $delete->handle($request),
        );
        // Public endpoint (no auth required via ALWAYS_OPEN_PREFIXES)
        $router->get(
            '/api/v1/public/menus',
            static fn (ServerRequestInterface $request) => $listPublic->handle($request),
        );
    }
}
