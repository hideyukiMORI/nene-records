<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateWebhookHandler
{
    /** @var list<string> */
    private const ALLOWED_EVENTS = ['entity.created', 'entity.updated', 'entity.deleted'];

    public function __construct(
        private CreateWebhookUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);
        $errors = $this->validate($body);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $url = trim((string) ($body['url'] ?? ''));
        $events = $this->parseEvents($body);
        $entityTypeId = isset($body['entity_type_id']) && $body['entity_type_id'] !== ''
            ? (int) $body['entity_type_id']
            : null;
        $secret = isset($body['secret']) && $body['secret'] !== ''
            ? trim((string) $body['secret'])
            : null;
        $isActive = isset($body['is_active']) ? (bool) $body['is_active'] : true;

        $output = $this->useCase->execute(new CreateWebhookInput(
            url: $url,
            events: $events,
            entityTypeId: $entityTypeId,
            secret: $secret,
            isActive: $isActive,
        ));

        return $this->response->create([
            'id' => $output->id,
            'url' => $output->url,
            'events' => $output->events,
            'entity_type_id' => $output->entityTypeId,
            'secret' => $output->secret,
            'is_active' => $output->isActive,
            'created_at' => $output->createdAt,
            'updated_at' => $output->updatedAt,
        ], 201)->withHeader('Location', '/api/v1/webhooks/' . $output->id);
    }

    /**
     * @param array<string, mixed> $body
     * @return list<ValidationError>
     */
    private function validate(array $body): array
    {
        $errors = [];
        $url = trim((string) ($body['url'] ?? ''));

        if ($url === '') {
            $errors[] = new ValidationError('url', 'URL is required.', 'required');
        } elseif (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $errors[] = new ValidationError('url', 'URL must be a valid URL.', 'invalid');
        }

        $events = $this->parseEvents($body);

        if ($events === []) {
            $errors[] = new ValidationError('events', 'At least one event is required.', 'required');
        } else {
            foreach ($events as $event) {
                if (!in_array($event, self::ALLOWED_EVENTS, true)) {
                    $errors[] = new ValidationError('events', 'Invalid event: ' . $event . '. Allowed: ' . implode(', ', self::ALLOWED_EVENTS), 'invalid');
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $body
     * @return list<string>
     */
    private function parseEvents(array $body): array
    {
        $raw = $body['events'] ?? [];

        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_map('strval', $raw)));
    }
}
