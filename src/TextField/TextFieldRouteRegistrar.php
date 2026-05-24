<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class TextFieldRouteRegistrar
{
    public function __construct(
        private ListTextFieldsHandler $listHandler,
        private GetTextFieldByIdHandler $getHandler,
        private CreateTextFieldHandler $createHandler,
        private UpdateTextFieldHandler $updateHandler,
        private DeleteTextFieldHandler $deleteHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $listHandler = $this->listHandler;
        $getHandler = $this->getHandler;
        $createHandler = $this->createHandler;
        $updateHandler = $this->updateHandler;
        $deleteHandler = $this->deleteHandler;

        $router->get('/api/v1/text-fields', static fn (ServerRequestInterface $request) => $listHandler->handle($request));
        $router->get('/api/v1/text-fields/{id}', static fn (ServerRequestInterface $request) => $getHandler->handle($request));
        $router->post('/api/v1/text-fields', static fn (ServerRequestInterface $request) => $createHandler->handle($request));
        $router->put('/api/v1/text-fields/{id}', static fn (ServerRequestInterface $request) => $updateHandler->handle($request));
        $router->delete('/api/v1/text-fields/{id}', static fn (ServerRequestInterface $request) => $deleteHandler->handle($request));
    }
}
