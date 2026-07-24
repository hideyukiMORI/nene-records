<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AnalyticsRouteRegistrar
{
    public function __construct(
        private GetAccessStatsByDateHandler $getAccessStatsHandler,
        private GetPopularEntitiesHandler $getPopularEntitiesHandler,
        private BeaconIngestHandler $beaconIngestHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $getAccessStatsHandler = $this->getAccessStatsHandler;
        $getPopularEntitiesHandler = $this->getPopularEntitiesHandler;
        $beaconIngestHandler = $this->beaconIngestHandler;

        $router->get(
            '/api/v1/analytics/access-stats',
            static fn (ServerRequestInterface $request) => $getAccessStatsHandler->handle($request),
        );
        $router->get(
            '/api/v1/analytics/popular-entities',
            static fn (ServerRequestInterface $request) => $getPopularEntitiesHandler->handle($request),
        );
        // Public LP beacon (Path B). Always-open under /api/v1/public/*; no auth.
        $router->post(
            '/api/v1/public/beacon',
            static fn (ServerRequestInterface $request) => $beaconIngestHandler->handle($request),
        );
    }
}
