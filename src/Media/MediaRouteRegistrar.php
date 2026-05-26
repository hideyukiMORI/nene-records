<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class MediaRouteRegistrar
{
    public function __construct(
        private UploadMediaHandler $uploadHandler,
        private ListMediaHandler $listHandler,
        private DeleteMediaHandler $deleteHandler,
        private ServeMediaHandler $serveHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $uploadHandler = $this->uploadHandler;
        $listHandler = $this->listHandler;
        $deleteHandler = $this->deleteHandler;
        $serveHandler = $this->serveHandler;

        $router->post('/api/v1/media', static fn (ServerRequestInterface $request) => $uploadHandler->handle($request));
        $router->get('/api/v1/media', static fn (ServerRequestInterface $request) => $listHandler->handle($request));
        $router->delete('/api/v1/media/{id}', static fn (ServerRequestInterface $request) => $deleteHandler->handle($request));
        $router->get('/media/{year}/{month}/{filename}', static fn (ServerRequestInterface $request) => $serveHandler->handle($request));
    }
}
