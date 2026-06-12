<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateFieldDefHandler
{
    public function __construct(
        private CreateFieldDefUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);
        $errors = FieldDefWriteValidator::validate($body);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new CreateFieldDefInput(
            entityTypeId: (int) $body['entity_type_id'],
            fieldKey: trim((string) $body['field_key']),
            dataType: trim((string) $body['data_type']),
            targetEntityTypeId: FieldDefWriteValidator::parseTargetEntityTypeId($body),
            cardinality: FieldDefWriteValidator::parseCardinality($body),
            region: FieldDefWriteValidator::parseRegion($body),
            displayOrder: FieldDefWriteValidator::parseDisplayOrder($body),
        ));

        return $this->response->create(
            FieldDefHttpMapper::toResponse(
                id: $output->id,
                entityTypeId: $output->entityTypeId,
                fieldKey: $output->fieldKey,
                dataType: $output->dataType,
                targetEntityTypeId: $output->targetEntityTypeId,
                cardinality: $output->cardinality,
                region: $output->region,
                displayOrder: $output->displayOrder,
            ),
            201,
            ['Location' => '/api/v1/field-defs/' . $output->id],
        );
    }
}
