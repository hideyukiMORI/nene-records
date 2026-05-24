<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateFieldDefHandler
{
    public function __construct(
        private UpdateFieldDefUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new FieldDefNotFoundException($id);
        }

        $body = JsonRequestBodyParser::parse($request);
        $errors = FieldDefWriteValidator::validate($body);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new UpdateFieldDefInput(
            id: $id,
            entityTypeId: (int) $body['entity_type_id'],
            fieldKey: trim((string) $body['field_key']),
            dataType: trim((string) $body['data_type']),
            targetEntityTypeId: FieldDefWriteValidator::parseTargetEntityTypeId($body),
            cardinality: FieldDefWriteValidator::parseCardinality($body),
        ));

        return $this->response->create(FieldDefHttpMapper::toResponse(
            id: $output->id,
            entityTypeId: $output->entityTypeId,
            fieldKey: $output->fieldKey,
            dataType: $output->dataType,
            targetEntityTypeId: $output->targetEntityTypeId,
            cardinality: $output->cardinality,
        ));
    }
}
