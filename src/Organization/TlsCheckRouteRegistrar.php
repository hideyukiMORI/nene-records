<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers the on-demand TLS gate (`GET /internal/tls-check`). Org-resolution
 * bypasses this path (see OrgResolverMiddleware::BYPASS_PREFIXES) because it
 * answers about other hosts and carries no tenant context of its own.
 */
final readonly class TlsCheckRouteRegistrar
{
    public function __construct(
        private TlsCheckHandler $handler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $handler = $this->handler;
        $router->get('/internal/tls-check', static fn (ServerRequestInterface $r) => $handler->handle($r));
    }
}
