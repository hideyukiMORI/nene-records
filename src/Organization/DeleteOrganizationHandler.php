<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteOrganizationHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $repository,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = (array) $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);
        $org = $this->repository->findById($id);

        if ($org === null) {
            throw new OrganizationNotFoundException($id);
        }

        $this->repository->delete($id);

        return $this->responseFactory->createResponse(204);
    }
}
