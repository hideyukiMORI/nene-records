<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateBoolFieldHandler
{
    public function __construct(
        private UpdateBoolFieldUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new BoolFieldNotFoundException($id);
        }

        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $fieldKey = trim((string) ($body['field_key'] ?? ''));

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

        $output = $this->useCase->execute(new UpdateBoolFieldInput(id: $id, fieldKey: $fieldKey, value: $value));

        return $this->response->create([
            'id'        => $output->id,
            'entity_id' => $output->entityId,
            'field_key' => $output->fieldKey,
            'value'     => $output->value,
        ]);
    }
}
