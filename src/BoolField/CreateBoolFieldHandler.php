<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateBoolFieldHandler
{
    public function __construct(
        private CreateBoolFieldUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $entityId = (int) ($body['entity_id'] ?? 0);
        $fieldKey = trim((string) ($body['field_key'] ?? ''));

        if ($entityId <= 0) {
            $errors[] = new ValidationError('entity_id', 'Entity id must be a positive integer.', 'invalid');
        }

        if ($fieldKey === '') {
            $errors[] = new ValidationError('field_key', 'Field key is required.', 'required');
        }

        if (!array_key_exists('value', $body)) {
            $errors[] = new ValidationError('value', 'Value is required.', 'required');
        } elseif (!is_bool($body['value'])) {
            $errors[] = new ValidationError('value', 'Value must be a boolean.', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        /** @var bool $value */
        $value = $body['value'];

        $output = $this->useCase->execute(new CreateBoolFieldInput(
            entityId: $entityId,
            fieldKey: $fieldKey,
            value: $value,
        ));

        return $this->response->create(
            [
                'id'         => $output->id,
                'entity_id'  => $output->entityId,
                'field_key'  => $output->fieldKey,
                'value'      => $output->value,
            ],
            201,
            ['Location' => '/api/v1/bool-fields/' . $output->id],
        );
    }
}
