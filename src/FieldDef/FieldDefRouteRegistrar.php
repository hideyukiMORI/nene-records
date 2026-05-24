<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class FieldDefRouteRegistrar
{
    public function __construct(
        private GetFieldDefByIdHandler $getHandler,
        private CreateFieldDefHandler $createHandler,
        private UpdateFieldDefHandler $updateHandler,
        private DeleteFieldDefHandler $deleteHandler,
        private ListFieldDefsHandler $listHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $getHandler = $this->getHandler;
        $createHandler = $this->createHandler;
        $updateHandler = $this->updateHandler;
        $deleteHandler = $this->deleteHandler;
        $listHandler = $this->listHandler;

        $router->get('/api/v1/field-defs', static fn (ServerRequestInterface $request) => $listHandler->handle($request));
        $router->get('/api/v1/field-defs/{id}', static fn (ServerRequestInterface $request) => $getHandler->handle($request));
        $router->post('/api/v1/field-defs', static fn (ServerRequestInterface $request) => $createHandler->handle($request));
        $router->put('/api/v1/field-defs/{id}', static fn (ServerRequestInterface $request) => $updateHandler->handle($request));
        $router->delete('/api/v1/field-defs/{id}', static fn (ServerRequestInterface $request) => $deleteHandler->handle($request));
    }
}
