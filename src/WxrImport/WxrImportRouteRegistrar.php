<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class WxrImportRouteRegistrar
{
    public function __construct(
        private WxrImportHttpHandler $handler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $handler = $this->handler;

        $router->post(
            '/api/v1/migration/wxr',
            static fn (ServerRequestInterface $request) => $handler->handle($request),
        );
    }
}
