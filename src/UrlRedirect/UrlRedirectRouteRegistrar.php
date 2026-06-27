<?php

declare(strict_types=1);

namespace NeNeRecords\UrlRedirect;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UrlRedirectRouteRegistrar
{
    public function __construct(
        private ImportRedirectsCsvHttpHandler $importHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $handler = $this->importHandler;

        // Admin-only via the /api/v1/migration/* prefix (AdminApiAuthMiddleware). #651 PR4
        $router->post(
            '/api/v1/migration/url-redirects',
            static fn (ServerRequestInterface $request) => $handler->handle($request),
        );
    }
}
