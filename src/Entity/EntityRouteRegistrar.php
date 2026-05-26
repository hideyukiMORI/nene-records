<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class EntityRouteRegistrar
{
    public function __construct(
        private GetEntityByIdHandler $getHandler,
        private CreateEntityHandler $createHandler,
        private UpdateEntityHandler $updateHandler,
        private DeleteEntityHandler $deleteHandler,
        private ListEntitiesHandler $listHandler,
        private ListEntityRevisionsHandler $listRevisionsHandler,
        private ExportEntitiesHandler $exportHandler,
        private ScheduleEntityHandler $scheduleHandler,
        private UnscheduleEntityHandler $unscheduleHandler,
        private ProcessScheduledPublishHandler $processScheduledHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $getHandler = $this->getHandler;
        $createHandler = $this->createHandler;
        $updateHandler = $this->updateHandler;
        $deleteHandler = $this->deleteHandler;
        $listHandler = $this->listHandler;
        $listRevisionsHandler = $this->listRevisionsHandler;
        $exportHandler = $this->exportHandler;
        $scheduleHandler = $this->scheduleHandler;
        $unscheduleHandler = $this->unscheduleHandler;
        $processScheduledHandler = $this->processScheduledHandler;

        $router->get('/api/v1/entities', static fn (ServerRequestInterface $request) => $listHandler->handle($request));
        $router->get('/api/v1/entities/export', static fn (ServerRequestInterface $request) => $exportHandler->handle($request));
        $router->post('/api/v1/entities/process-scheduled', static fn (ServerRequestInterface $request) => $processScheduledHandler->handle($request));
        $router->get('/api/v1/entities/{id}', static fn (ServerRequestInterface $request) => $getHandler->handle($request));
        $router->post('/api/v1/entities', static fn (ServerRequestInterface $request) => $createHandler->handle($request));
        $router->put('/api/v1/entities/{id}', static fn (ServerRequestInterface $request) => $updateHandler->handle($request));
        $router->delete('/api/v1/entities/{id}', static fn (ServerRequestInterface $request) => $deleteHandler->handle($request));
        $router->get(
            '/api/v1/entities/{id}/revisions',
            static fn (ServerRequestInterface $request) => $listRevisionsHandler->handle($request),
        );
        $router->post(
            '/api/v1/entities/{id}/schedule',
            static fn (ServerRequestInterface $request) => $scheduleHandler->handle($request),
        );
        $router->delete(
            '/api/v1/entities/{id}/schedule',
            static fn (ServerRequestInterface $request) => $unscheduleHandler->handle($request),
        );
    }
}
