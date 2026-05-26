<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Webhook;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Webhook\CreateWebhookHandler;
use NeNeRecords\Webhook\CreateWebhookUseCase;
use NeNeRecords\Webhook\DeleteWebhookHandler;
use NeNeRecords\Webhook\DeleteWebhookUseCase;
use NeNeRecords\Webhook\GetWebhookByIdHandler;
use NeNeRecords\Webhook\GetWebhookByIdUseCase;
use NeNeRecords\Webhook\ListWebhooksHandler;
use NeNeRecords\Webhook\ListWebhooksUseCase;
use NeNeRecords\Webhook\UpdateWebhookHandler;
use NeNeRecords\Webhook\UpdateWebhookUseCase;
use NeNeRecords\Webhook\WebhookNotFoundExceptionHandler;
use NeNeRecords\Webhook\WebhookRouteRegistrar;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class WebhookHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryWebhookRepository $webhooks;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->webhooks = new InMemoryWebhookRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new WebhookRouteRegistrar(
            new ListWebhooksHandler(new ListWebhooksUseCase($this->webhooks), $jsonResponse),
            new GetWebhookByIdHandler(new GetWebhookByIdUseCase($this->webhooks), $jsonResponse),
            new CreateWebhookHandler(new CreateWebhookUseCase($this->webhooks), $jsonResponse),
            new UpdateWebhookHandler(new UpdateWebhookUseCase($this->webhooks), $jsonResponse),
            new DeleteWebhookHandler(new DeleteWebhookUseCase($this->webhooks), $jsonResponse),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new WebhookNotFoundExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testListReturnsEmptyItems(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/webhooks'),
        );

        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $payload['items']);
    }

    public function testPostCreatesWebhookAndReturns201WithLocation(): void
    {
        $body = $this->factory->createStream(json_encode([
            'url' => 'https://example.com/hook',
            'events' => ['entity.created', 'entity.updated'],
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/webhooks')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertStringStartsWith('/api/v1/webhooks/', $response->getHeaderLine('Location'));
        self::assertSame('https://example.com/hook', $payload['url']);
        self::assertSame(['entity.created', 'entity.updated'], $payload['events']);
        self::assertNull($payload['entity_type_id']);
        self::assertNull($payload['secret']);
        self::assertTrue($payload['is_active']);
    }

    public function testPostWithAllFields(): void
    {
        $body = $this->factory->createStream(json_encode([
            'url' => 'https://example.com/hook',
            'events' => ['entity.deleted'],
            'entity_type_id' => 5,
            'secret' => 'my-secret',
            'is_active' => false,
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/webhooks')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(5, $payload['entity_type_id']);
        self::assertSame('my-secret', $payload['secret']);
        self::assertFalse($payload['is_active']);
    }

    public function testPostWithMissingUrlReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'events' => ['entity.created'],
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/webhooks')->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testPostWithInvalidUrlReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'url' => 'not-a-url',
            'events' => ['entity.created'],
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/webhooks')->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testPostWithInvalidEventReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'url' => 'https://example.com/hook',
            'events' => ['entity.unknown'],
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/webhooks')->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testGetByIdReturns200(): void
    {
        $created = $this->createWebhook('https://example.com/hook', ['entity.created']);

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/webhooks/{$created['id']}"),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($created['id'], $payload['id']);
        self::assertSame('https://example.com/hook', $payload['url']);
    }

    public function testGetByIdReturns404WhenNotFound(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/webhooks/999'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testPutUpdatesWebhook(): void
    {
        $created = $this->createWebhook('https://example.com/hook', ['entity.created']);

        $body = $this->factory->createStream(json_encode([
            'url' => 'https://updated.example.com/hook',
            'events' => ['entity.updated', 'entity.deleted'],
            'is_active' => false,
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', "https://example.test/api/v1/webhooks/{$created['id']}")->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('https://updated.example.com/hook', $payload['url']);
        self::assertSame(['entity.updated', 'entity.deleted'], $payload['events']);
        self::assertFalse($payload['is_active']);
    }

    public function testPutReturns404WhenNotFound(): void
    {
        $body = $this->factory->createStream(json_encode([
            'url' => 'https://example.com/hook',
            'events' => ['entity.created'],
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', 'https://example.test/api/v1/webhooks/999')->withBody($body),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testDeleteReturns204(): void
    {
        $created = $this->createWebhook('https://example.com/hook', ['entity.created']);

        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/webhooks/{$created['id']}"),
        );

        self::assertSame(204, $response->getStatusCode());

        $getAfter = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/webhooks/{$created['id']}"),
        );
        self::assertSame(404, $getAfter->getStatusCode());
    }

    public function testListReturnsCreatedWebhooks(): void
    {
        $this->createWebhook('https://example.com/hook-a', ['entity.created']);
        $this->createWebhook('https://example.com/hook-b', ['entity.updated', 'entity.deleted']);

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/webhooks'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(2, $payload['items']);
    }

    /**
     * @param list<string> $events
     * @return array<string, mixed>
     */
    private function createWebhook(string $url, array $events): array
    {
        $body = $this->factory->createStream(json_encode(
            compact('url', 'events'),
            JSON_THROW_ON_ERROR,
        ));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/webhooks')->withBody($body),
        );

        return $this->decodeJson($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(ResponseInterface $response): array
    {
        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
