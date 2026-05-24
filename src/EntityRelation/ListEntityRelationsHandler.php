<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeRecords\Entity\EntityNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListEntityRelationsHandler
{
    public function __construct(
        private ListEntityRelationsUseCaseInterface $useCase,
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

        $fieldKey = trim((string) ($request->getQueryParams()['field_key'] ?? ''));

        if ($fieldKey === '') {
            throw new ValidationException([
                new ValidationError('field_key', 'Field key is required.', 'required'),
            ]);
        }

        $output = $this->useCase->execute(new ListEntityRelationsInput($entityId, $fieldKey));

        return $this->response->create([
            'items' => array_map(
                static fn (EntityRelationListItem $item) => [
                    'field_key' => $item->fieldKey,
                    'target_entity_id' => $item->targetEntityId,
                ],
                $output->items,
            ),
        ]);
    }
}
