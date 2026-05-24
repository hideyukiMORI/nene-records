<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeRecords\Entity\EntityNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AttachEntityRelationHandler
{
    public function __construct(
        private AttachEntityRelationUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $entityId = (int) ($parameters['entityId'] ?? 0);

        if ($entityId <= 0) {
            throw new EntityNotFoundException($entityId);
        }

        $body = JsonRequestBodyParser::parse($request);
        $fieldKey = trim((string) ($body['field_key'] ?? ''));
        $targetEntityIdRaw = $body['target_entity_id'] ?? null;

        $errors = [];

        if ($fieldKey === '') {
            $errors[] = new ValidationError('field_key', 'Field key is required.', 'required');
        }

        if (!is_int($targetEntityIdRaw) && !(is_string($targetEntityIdRaw) && ctype_digit($targetEntityIdRaw))) {
            $errors[] = new ValidationError('target_entity_id', 'Target entity id is required.', 'required');
        } else {
            $targetEntityId = (int) $targetEntityIdRaw;

            if ($targetEntityId <= 0) {
                $errors[] = new ValidationError('target_entity_id', 'Target entity id must be a positive integer.', 'invalid');
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $targetEntityId = (int) $targetEntityIdRaw;

        $output = $this->useCase->execute(new AttachEntityRelationInput(
            entityId: $entityId,
            fieldKey: $fieldKey,
            targetEntityId: $targetEntityId,
        ));

        return $this->response->create([
            'field_key' => $output->fieldKey,
            'target_entity_id' => $output->targetEntityId,
        ], 201);
    }
}
