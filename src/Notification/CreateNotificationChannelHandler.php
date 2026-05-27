<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateNotificationChannelHandler
{
    private const VALID_TYPES = ['email', 'slack', 'discord', 'chatwork', 'webhook'];

    public function __construct(
        private CreateNotificationChannelUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);
        $errors = [];

        $channelType = trim((string) ($body['channel_type'] ?? ''));
        $label = trim((string) ($body['label'] ?? ''));
        $isEnabled = isset($body['is_enabled']) ? (bool) $body['is_enabled'] : true;
        $config = isset($body['config']) && is_array($body['config']) ? $body['config'] : [];

        if ($channelType === '') {
            $errors[] = new ValidationError('channel_type', 'channel_type is required.', 'required');
        } elseif (!in_array($channelType, self::VALID_TYPES, true)) {
            $errors[] = new ValidationError('channel_type', 'Invalid channel_type. Allowed: ' . implode(', ', self::VALID_TYPES) . '.', 'enum');
        }

        if ($label === '') {
            $errors[] = new ValidationError('label', 'label is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $channel = $this->useCase->execute(new CreateNotificationChannelInput(
            channelType: $channelType,
            label: $label,
            isEnabled: $isEnabled,
            config: $config,
        ));

        return $this->response->create($this->serialize($channel), 201);
    }

    /** @return array<string,mixed> */
    private function serialize(NotificationChannel $ch): array
    {
        return [
            'id'           => $ch->id,
            'channel_type' => $ch->channelType,
            'label'        => $ch->label,
            'is_enabled'   => $ch->isEnabled,
            'config'       => $ch->config,
            'created_at'   => $ch->createdAt,
            'updated_at'   => $ch->updatedAt,
        ];
    }
}
