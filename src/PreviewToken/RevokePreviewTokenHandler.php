<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

use Nene2\Routing\Router;
use NeNeRecords\Entity\EntityNotFoundException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RevokePreviewTokenHandler
{
    public function __construct(
        private RevokePreviewTokenUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new EntityNotFoundException($id);
        }

        $this->useCase->execute(new RevokePreviewTokenInput(entityId: $id));

        return $this->responseFactory->createResponse(204);
    }
}
