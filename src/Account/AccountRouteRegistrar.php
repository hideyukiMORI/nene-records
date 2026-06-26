<?php

declare(strict_types=1);

namespace NeNeRecords\Account;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AccountRouteRegistrar
{
    public function __construct(
        private GetAccountHandler $handler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $handler = $this->handler;

        $router->get(
            '/api/v1/account',
            static fn (ServerRequestInterface $request) => $handler->handle($request),
        );
    }
}
