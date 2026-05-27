<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class DeleteOrganizationHandler implements RequestHandlerInterface
{
    public function __construct(
        private DeleteOrganizationUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = (array) $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id         = (int) ($parameters['id'] ?? 0);

        $this->useCase->execute(new DeleteOrganizationInput(id: $id));

        return $this->responseFactory->createResponse(204);
    }
}
