<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteUserHandler
{
    public function __construct(
        private DeleteUserUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);
        $claims = $request->getAttribute('nene2.auth.claims');
        $currentUserEmail = is_array($claims) ? (string) ($claims['sub'] ?? '') : '';

        $this->useCase->execute(new DeleteUserInput(id: $id, currentUserEmail: $currentUserEmail));

        return $this->responseFactory->createResponse(204);
    }
}
