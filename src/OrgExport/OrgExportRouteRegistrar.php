<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class OrgExportRouteRegistrar
{
    public function __construct(
        private OrgExportHandler $exportHandler,
        private OrgImportHandler $importHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $export = $this->exportHandler;
        $import = $this->importHandler;

        $router->get(
            '/api/v1/superadmin/organizations/{id}/export',
            static fn (ServerRequestInterface $r) => $export->handle($r),
        );
        $router->post(
            '/api/v1/superadmin/organizations/{id}/import',
            static fn (ServerRequestInterface $r) => $import->handle($r),
        );
    }
}
