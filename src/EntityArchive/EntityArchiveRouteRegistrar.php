<?php

declare(strict_types=1);

namespace NeNeRecords\EntityArchive;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class EntityArchiveRouteRegistrar
{
    public function __construct(
        private GetEntityArchiveCsvHandler $csvHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $csvHandler = $this->csvHandler;

        $router->get(
            '/api/v1/entity-types/{entity_type_id}/archive.csv',
            static fn (ServerRequestInterface $request) => $csvHandler->handle($request),
        );
    }
}
