<?php

declare(strict_types=1);

namespace NeNeRecords\Dashboard;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DashboardRouteRegistrar
{
    public function __construct(
        private GetDashboardSummaryHandler $handler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $handler = $this->handler;

        $router->get(
            '/api/v1/dashboard',
            static fn (ServerRequestInterface $request) => $handler->handle($request),
        );
    }
}
