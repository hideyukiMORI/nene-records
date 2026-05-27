<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class OrganizationRouteRegistrar
{
    public function __construct(
        private ListOrganizationsHandler $listHandler,
        private GetOrganizationByIdHandler $getHandler,
        private CreateOrganizationHandler $createHandler,
        private UpdateOrganizationHandler $updateHandler,
        private DeleteOrganizationHandler $deleteHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $get = $this->getHandler;
        $create = $this->createHandler;
        $update = $this->updateHandler;
        $delete = $this->deleteHandler;

        $router->get('/api/v1/organizations', static fn (ServerRequestInterface $r) => $list->handle($r));
        $router->get('/api/v1/organizations/{id}', static fn (ServerRequestInterface $r) => $get->handle($r));
        $router->post('/api/v1/organizations', static fn (ServerRequestInterface $r) => $create->handle($r));
        $router->patch('/api/v1/organizations/{id}', static fn (ServerRequestInterface $r) => $update->handle($r));
        $router->delete('/api/v1/organizations/{id}', static fn (ServerRequestInterface $r) => $delete->handle($r));
    }
}
