<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateTextFieldHandler
{
    public function __construct(
        private CreateTextFieldUseCaseInterface $useCase,
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
        }

        $valueRaw = array_key_exists('value', $body) ? trim((string) $body['value']) : '';

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new CreateTextFieldInput(
            entityId: $entityId,
            fieldKey: $fieldKey,
            value: $valueRaw,
        ));

        return $this->response->create(
            [
                'id'         => $output->id,
                'entity_id'  => $output->entityId,
                'field_key'  => $output->fieldKey,
                'value'      => $output->value,
            ],
            201,
            ['Location' => '/api/v1/text-fields/' . $output->id],
        );
    }
}
