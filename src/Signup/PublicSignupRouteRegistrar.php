<?php

declare(strict_types=1);

namespace NeNeRecords\Signup;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers `POST /api/v1/public/signup`. Public (AdminApiAuthMiddleware opens
 * `/api/v1/public/`) and org-resolution bypassed (OrgResolverMiddleware) since it
 * provisions a brand-new tenant.
 */
final readonly class PublicSignupRouteRegistrar
{
    public function __construct(
        private PublicSignupHandler $handler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $handler = $this->handler;
        $router->post('/api/v1/public/signup', static fn (ServerRequestInterface $r) => $handler->handle($r));
    }
}
