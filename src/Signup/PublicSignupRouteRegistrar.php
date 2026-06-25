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
        private ConfirmEmailHandler $confirmEmail,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $handler = $this->handler;
        $confirm = $this->confirmEmail;
        $router->post('/api/v1/public/signup', static fn (ServerRequestInterface $r) => $handler->handle($r));
        // Public (always-open /api/v1/auth/) email-verification confirm.
        $router->post('/api/v1/auth/confirm-email', static fn (ServerRequestInterface $r) => $confirm->handle($r));
    }
}
