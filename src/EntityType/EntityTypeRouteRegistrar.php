<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class EntityTypeRouteRegistrar
{
    public function __construct(
        private GetEntityTypeByIdHandler $getHandler,
        private CreateEntityTypeHandler $createHandler,
        private UpdateEntityTypeHandler $updateHandler,
        private DeleteEntityTypeHandler $deleteHandler,
        private ListEntityTypesHandler $listHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $getHandler = $this->getHandler;
        $createHandler = $this->createHandler;
        $updateHandler = $this->updateHandler;
        $deleteHandler = $this->deleteHandler;
        $listHandler = $this->listHandler;

        $router->get('/api/v1/entity-types', static fn (ServerRequestInterface $request) => $listHandler->handle($request));
        $router->get('/api/v1/entity-types/{id}', static fn (ServerRequestInterface $request) => $getHandler->handle($request));
        $router->post('/api/v1/entity-types', static fn (ServerRequestInterface $request) => $createHandler->handle($request));
        $router->put('/api/v1/entity-types/{id}', static fn (ServerRequestInterface $request) => $updateHandler->handle($request));
        $router->delete('/api/v1/entity-types/{id}', static fn (ServerRequestInterface $request) => $deleteHandler->handle($request));
    }
}
