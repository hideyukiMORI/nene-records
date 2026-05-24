<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateSettingHandler
{
    private const KEY_PATTERN = '/^[a-z][a-z0-9_]*$/';

    public function __construct(
        private UpdateSettingUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $settingKey = trim((string) ($parameters['key'] ?? ''));

        if ($settingKey === '' || preg_match(self::KEY_PATTERN, $settingKey) !== 1) {
            throw new SettingKeyNotFoundException($settingKey !== '' ? $settingKey : 'unknown');
        }

        $body = JsonRequestBodyParser::parse($request);

        if (!array_key_exists('value', $body)) {
            throw new ValidationException([
                new ValidationError('value', 'Value is required.', 'required'),
            ]);
        }

        if (!is_string($body['value'])) {
            throw new ValidationException([
                new ValidationError('value', 'Value must be a string.', 'type'),
            ]);
        }

        $output = $this->useCase->execute(new UpdateSettingInput(
            settingKey: $settingKey,
            value: $body['value'],
        ));

        return $this->response->create([
            'setting_key' => $output->settingKey,
            'value' => $output->value,
            'updated_at' => $output->updatedAt,
        ]);
    }
}
