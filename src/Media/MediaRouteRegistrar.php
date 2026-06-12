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
        private UpdateMediaAltHandler $updateAltHandler,
        private ServeDerivativeHandler $serveDerivativeHandler,
        private ListMediaUsagesHandler $usagesHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $uploadHandler = $this->uploadHandler;
        $listHandler = $this->listHandler;
        $deleteHandler = $this->deleteHandler;
        $serveHandler = $this->serveHandler;
        $updateAltHandler = $this->updateAltHandler;
        $serveDerivativeHandler = $this->serveDerivativeHandler;
        $usagesHandler = $this->usagesHandler;

        $router->post('/api/v1/media', static fn (ServerRequestInterface $request) => $uploadHandler->handle($request));
        $router->get('/api/v1/media', static fn (ServerRequestInterface $request) => $listHandler->handle($request));
        $router->get('/api/v1/media/{id}/usages', static fn (ServerRequestInterface $request) => $usagesHandler->handle($request));
        $router->patch('/api/v1/media/{id}', static fn (ServerRequestInterface $request) => $updateAltHandler->handle($request));
        $router->delete('/api/v1/media/{id}', static fn (ServerRequestInterface $request) => $deleteHandler->handle($request));
        // 4-segment derivative route must be registered before the 3-segment original.
        $router->get('/media/{preset}/{year}/{month}/{filename}', static fn (ServerRequestInterface $request) => $serveDerivativeHandler->handle($request));
        $router->get('/media/{year}/{month}/{filename}', static fn (ServerRequestInterface $request) => $serveHandler->handle($request));
    }
}
