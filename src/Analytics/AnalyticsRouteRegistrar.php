<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AnalyticsRouteRegistrar
{
    public function __construct(
        private GetAccessStatsByDateHandler $getAccessStatsHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $getAccessStatsHandler = $this->getAccessStatsHandler;

        $router->get(
            '/api/v1/analytics/access-stats',
            static fn (ServerRequestInterface $request) => $getAccessStatsHandler->handle($request),
        );
    }
}
