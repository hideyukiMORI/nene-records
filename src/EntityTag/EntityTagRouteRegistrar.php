<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class EntityTagRouteRegistrar
{
    public function __construct(
        private ListEntityTagsHandler $listHandler,
        private AttachEntityTagHandler $attachHandler,
        private DetachEntityTagHandler $detachHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $listHandler = $this->listHandler;
        $attachHandler = $this->attachHandler;
        $detachHandler = $this->detachHandler;

        $router->get(
            '/api/v1/entities/{entityId}/tags',
            static fn (ServerRequestInterface $request) => $listHandler->handle($request),
        );
        $router->post(
            '/api/v1/entities/{entityId}/tags',
            static fn (ServerRequestInterface $request) => $attachHandler->handle($request),
        );
        $router->delete(
            '/api/v1/entities/{entityId}/tags/{tagId}',
            static fn (ServerRequestInterface $request) => $detachHandler->handle($request),
        );
    }
}
