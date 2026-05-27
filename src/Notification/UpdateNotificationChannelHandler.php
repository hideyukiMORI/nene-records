<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateNotificationChannelHandler
{
    public function __construct(
        private UpdateNotificationChannelUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($params['id'] ?? 0);

        $body = JsonRequestBodyParser::parse($request);
        $errors = [];

        $label = trim((string) ($body['label'] ?? ''));
        $isEnabled = isset($body['is_enabled']) ? (bool) $body['is_enabled'] : true;
        $config = isset($body['config']) && is_array($body['config']) ? $body['config'] : [];

        if ($label === '') {
            $errors[] = new ValidationError('label', 'label is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->useCase->execute(new UpdateNotificationChannelInput(
            id: $id,
            label: $label,
            isEnabled: $isEnabled,
            config: $config,
        ));

        return $this->response->create(['success' => true]);
    }
}
