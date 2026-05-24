<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateFieldDefHandler
{
    /** @var list<string> */
    private const ALLOWED_DATA_TYPES = ['text', 'int', 'enum', 'bool', 'datetime'];

    public function __construct(
        private CreateFieldDefUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $entityTypeId = (int) ($body['entity_type_id'] ?? 0);
        $fieldKey = trim((string) ($body['field_key'] ?? ''));
        $dataType = trim((string) ($body['data_type'] ?? ''));

        if ($entityTypeId <= 0) {
            $errors[] = new ValidationError('entity_type_id', 'Entity type id must be a positive integer.', 'invalid');
        }

        if ($fieldKey === '') {
            $errors[] = new ValidationError('field_key', 'Field key is required.', 'required');
        }

        if ($dataType === '') {
            $errors[] = new ValidationError('data_type', 'Data type is required.', 'required');
        } elseif (!in_array($dataType, self::ALLOWED_DATA_TYPES, true)) {
            $errors[] = new ValidationError('data_type', 'Data type must be one of: text, int, enum, bool, datetime.', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new CreateFieldDefInput(
            entityTypeId: $entityTypeId,
            fieldKey: $fieldKey,
            dataType: $dataType,
        ));

        return $this->response->create(
            [
                'id' => $output->id,
                'entity_type_id' => $output->entityTypeId,
                'field_key' => $output->fieldKey,
                'data_type' => $output->dataType,
            ],
            201,
            ['Location' => '/api/v1/field-defs/' . $output->id],
        );
    }
}
