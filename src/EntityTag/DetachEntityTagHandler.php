<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

use Nene2\Routing\Router;
use NeNeRecords\Entity\EntityNotFoundException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DetachEntityTagHandler
{
    public function __construct(
        private DetachEntityTagUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $entityId = (int) ($parameters['entityId'] ?? 0);
        $tagId = (int) ($parameters['tagId'] ?? 0);

        if ($entityId <= 0) {
            throw new EntityNotFoundException($entityId);
        }

        if ($tagId <= 0) {
            throw new EntityTagNotAttachedException($entityId, $tagId);
        }

        $this->useCase->execute(new DetachEntityTagInput($entityId, $tagId));

        return $this->responseFactory->createResponse(204);
    }
}
