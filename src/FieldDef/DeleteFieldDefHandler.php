<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteFieldDefHandler
{
    public function __construct(
        private DeleteFieldDefUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new FieldDefNotFoundException($id);
        }

        $this->useCase->execute(new DeleteFieldDefInput($id));

        return $this->responseFactory->createResponse(204);
    }
}
