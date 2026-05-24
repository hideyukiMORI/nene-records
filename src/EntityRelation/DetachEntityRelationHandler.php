<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeRecords\Entity\EntityNotFoundException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DetachEntityRelationHandler
{
    public function __construct(
        private DetachEntityRelationUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $entityId = (int) ($parameters['entityId'] ?? 0);
        $targetEntityId = (int) ($parameters['targetEntityId'] ?? 0);

        if ($entityId <= 0) {
            throw new EntityNotFoundException($entityId);
        }

        if ($targetEntityId <= 0) {
            throw new RelationNotAttachedException($entityId, $targetEntityId, '');
        }

        $fieldKey = trim((string) ($request->getQueryParams()['field_key'] ?? ''));

        if ($fieldKey === '') {
            throw new ValidationException([
                new ValidationError('field_key', 'Field key is required.', 'required'),
            ]);
        }

        $this->useCase->execute(new DetachEntityRelationInput($entityId, $fieldKey, $targetEntityId));

        return $this->responseFactory->createResponse(204);
    }
}
