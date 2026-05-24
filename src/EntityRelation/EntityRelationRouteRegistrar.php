<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class EntityRelationRouteRegistrar
{
    public function __construct(
        private ListEntityRelationsHandler $listHandler,
        private AttachEntityRelationHandler $attachHandler,
        private DetachEntityRelationHandler $detachHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $listHandler = $this->listHandler;
        $attachHandler = $this->attachHandler;
        $detachHandler = $this->detachHandler;

        $router->get(
            '/api/v1/entities/{entityId}/relations',
            static fn (ServerRequestInterface $request) => $listHandler->handle($request),
        );
        $router->post(
            '/api/v1/entities/{entityId}/relations',
            static fn (ServerRequestInterface $request) => $attachHandler->handle($request),
        );
        $router->delete(
            '/api/v1/entities/{entityId}/relations/{targetEntityId}',
            static fn (ServerRequestInterface $request) => $detachHandler->handle($request),
        );
    }
}
