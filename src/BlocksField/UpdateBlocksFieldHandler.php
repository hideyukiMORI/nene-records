<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateBlocksFieldHandler
{
    public function __construct(
        private UpdateBlocksFieldUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new BlocksFieldNotFoundException($id);
        }

        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $fieldKey = trim((string) ($body['field_key'] ?? ''));

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

        $localeRaw = isset($body['locale']) && is_string($body['locale']) && $body['locale'] !== ''
            ? $body['locale']
            : null;

        $output = $this->useCase->execute(new UpdateBlocksFieldInput(
            id: $id,
            fieldKey: $fieldKey,
            value: $valueRaw,
            locale: $localeRaw,
        ));

        return $this->response->create([
            'id'        => $output->id,
            'entity_id' => $output->entityId,
            'field_key' => $output->fieldKey,
            'value'     => $output->value,
            'locale'    => $output->locale,
        ]);
    }
}
