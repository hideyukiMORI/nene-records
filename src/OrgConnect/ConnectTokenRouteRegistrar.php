<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ConnectTokenRouteRegistrar
{
    public function __construct(
        private ListConnectTokensHandler $listHandler,
        private SaveConnectTokenHandler $saveHandler,
        private DeleteConnectTokenHandler $deleteHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $save = $this->saveHandler;
        $delete = $this->deleteHandler;

        // No read route for a single token: the list already carries everything that is
        // safe to return, so a per-service GET would only add another surface to audit.
        $router->get(
            '/api/v1/connect-tokens',
            static fn (ServerRequestInterface $request) => $list->handle($request),
        );
        $router->put(
            '/api/v1/connect-tokens/{service}',
            static fn (ServerRequestInterface $request) => $save->handle($request),
        );
        $router->delete(
            '/api/v1/connect-tokens/{service}',
            static fn (ServerRequestInterface $request) => $delete->handle($request),
        );
    }
}
