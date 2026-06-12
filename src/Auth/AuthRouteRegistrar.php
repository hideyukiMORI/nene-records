<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AuthRouteRegistrar
{
    public function __construct(
        private LoginHandler $loginHandler,
        private LogoutHandler $logoutHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $loginHandler = $this->loginHandler;
        $logoutHandler = $this->logoutHandler;

        $router->post('/api/v1/auth/login', static fn (ServerRequestInterface $request) => $loginHandler->handle($request));
        $router->post('/api/v1/auth/logout', static fn (ServerRequestInterface $request) => $logoutHandler->handle($request));
    }
}
