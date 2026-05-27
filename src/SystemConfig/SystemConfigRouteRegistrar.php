<?php

declare(strict_types=1);

namespace NeNeRecords\SystemConfig;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class SystemConfigRouteRegistrar
{
    public function __construct(
        private GetSystemConfigHandler $getHandler,
        private UpdateSystemConfigHandler $updateHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $get    = $this->getHandler;
        $update = $this->updateHandler;

        $router->get(
            '/api/v1/superadmin/system-config',
            static fn (ServerRequestInterface $r) => $get->handle($r),
        );
        $router->patch(
            '/api/v1/superadmin/system-config',
            static fn (ServerRequestInterface $r) => $update->handle($r),
        );
    }
}
