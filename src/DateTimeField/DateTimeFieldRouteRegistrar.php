<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DateTimeFieldRouteRegistrar
{
    public function __construct(
        private ListDateTimeFieldsHandler $listHandler,
        private GetDateTimeFieldByIdHandler $getHandler,
        private CreateDateTimeFieldHandler $createHandler,
        private UpdateDateTimeFieldHandler $updateHandler,
        private DeleteDateTimeFieldHandler $deleteHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $listHandler = $this->listHandler;
        $getHandler = $this->getHandler;
        $createHandler = $this->createHandler;
        $updateHandler = $this->updateHandler;
        $deleteHandler = $this->deleteHandler;

        $router->get('/api/v1/datetime-fields', static fn (ServerRequestInterface $request) => $listHandler->handle($request));
        $router->get('/api/v1/datetime-fields/{id}', static fn (ServerRequestInterface $request) => $getHandler->handle($request));
        $router->post('/api/v1/datetime-fields', static fn (ServerRequestInterface $request) => $createHandler->handle($request));
        $router->put('/api/v1/datetime-fields/{id}', static fn (ServerRequestInterface $request) => $updateHandler->handle($request));
        $router->delete('/api/v1/datetime-fields/{id}', static fn (ServerRequestInterface $request) => $deleteHandler->handle($request));
    }
}
