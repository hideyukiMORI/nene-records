<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class SettingRouteRegistrar
{
    public function __construct(
        private ListSettingsHandler $listHandler,
        private ListPublicSettingsHandler $listPublicHandler,
        private UpdateSettingHandler $updateHandler,
        private ListSettingRevisionsHandler $listRevisionsHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $listHandler = $this->listHandler;
        $listPublicHandler = $this->listPublicHandler;
        $updateHandler = $this->updateHandler;
        $listRevisionsHandler = $this->listRevisionsHandler;

        $router->get('/api/v1/settings', static fn (ServerRequestInterface $request) => $listHandler->handle($request));
        $router->put('/api/v1/settings/{key}', static fn (ServerRequestInterface $request) => $updateHandler->handle($request));
        $router->get(
            '/api/v1/settings/{key}/revisions',
            static fn (ServerRequestInterface $request) => $listRevisionsHandler->handle($request),
        );
        $router->get(
            '/api/v1/public/settings',
            static fn (ServerRequestInterface $request) => $listPublicHandler->handle($request),
        );
    }
}
