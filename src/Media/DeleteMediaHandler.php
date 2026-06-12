<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteMediaHandler
{
    public function __construct(
        private DeleteMediaUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new MediaNotFoundException($id);
        }

        $this->useCase->execute(new DeleteMediaInput(id: $id));

        return $this->responseFactory->createResponse(204);
    }
}
