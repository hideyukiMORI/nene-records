<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DataMigrationRouteRegistrar
{
    public function __construct(
        private GetDataMigrationStatusHandler $statusHandler,
        private AssignOrganizationHandler $assignHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $status = $this->statusHandler;
        $assign = $this->assignHandler;

        $router->get(
            '/api/v1/superadmin/data-migration/status',
            static fn (ServerRequestInterface $r) => $status->handle($r),
        );
        $router->post(
            '/api/v1/superadmin/data-migration/assign-org',
            static fn (ServerRequestInterface $r) => $assign->handle($r),
        );
    }
}
