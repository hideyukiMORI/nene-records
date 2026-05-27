<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Notification;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Mail\MailerInterface;
use NeNeRecords\Notification\Channel\ChatWorkChannel;
use NeNeRecords\Notification\Channel\DiscordChannel;
use NeNeRecords\Notification\Channel\EmailChannel;
use NeNeRecords\Notification\Channel\SlackChannel;
use NeNeRecords\Notification\Channel\WebhookChannel;
use NeNeRecords\Notification\CreateNotificationChannelHandler;
use NeNeRecords\Notification\CreateNotificationChannelUseCase;
use NeNeRecords\Notification\DeleteNotificationChannelHandler;
use NeNeRecords\Notification\DeleteNotificationChannelUseCase;
use NeNeRecords\Notification\ListNotificationChannelsHandler;
use NeNeRecords\Notification\ListNotificationChannelsUseCase;
use NeNeRecords\Notification\NotificationChannelNotFoundExceptionHandler;
use NeNeRecords\Notification\NotificationRouteRegistrar;
use NeNeRecords\Notification\TestNotificationChannelHandler;
use NeNeRecords\Notification\TestNotificationChannelUseCase;
use NeNeRecords\Notification\UpdateNotificationChannelHandler;
use NeNeRecords\Notification\UpdateNotificationChannelUseCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class NotificationChannelHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryNotificationChannelRepository $repository;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->repository = new InMemoryNotificationChannelRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $slackChannel = new SlackChannel();
        $discordChannel = new DiscordChannel();
        $chatWorkChannel = new ChatWorkChannel();
        $webhookChannel = new WebhookChannel();
        $emailStub = new EmailChannel($this->createStub(MailerInterface::class));

        $registrar = new NotificationRouteRegistrar(
            listHandler: new ListNotificationChannelsHandler(
                new ListNotificationChannelsUseCase($this->repository),
                $jsonResponse,
            ),
            createHandler: new CreateNotificationChannelHandler(
                new CreateNotificationChannelUseCase($this->repository),
                $jsonResponse,
            ),
            updateHandler: new UpdateNotificationChannelHandler(
                new UpdateNotificationChannelUseCase($this->repository),
                $jsonResponse,
            ),
            deleteHandler: new DeleteNotificationChannelHandler(
                new DeleteNotificationChannelUseCase($this->repository),
                $this->factory,
            ),
            testHandler: new TestNotificationChannelHandler(
                new TestNotificationChannelUseCase(
                    channels: $this->repository,
                    emailChannel: $emailStub,
                    slackChannel: $slackChannel,
                    discordChannel: $discordChannel,
                    chatWorkChannel: $chatWorkChannel,
                    webhookChannel: $webhookChannel,
                ),
                $jsonResponse,
            ),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new NotificationChannelNotFoundExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    // ── GET /api/v1/notification-channels ────────────────────────────────────

    public function testListReturnsEmptyItemsInitially(): void
    {
        $response = $this->request('GET', '/api/v1/notification-channels');

        self::assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        self::assertSame([], $body['items']);
    }

    public function testListReturnsCreatedChannels(): void
    {
        $this->repository->create('email', 'Email Alert', true, ['to_address' => 'admin@example.com']);
        $this->repository->create('slack', 'Slack Dev', false, []);

        $response = $this->request('GET', '/api/v1/notification-channels');

        self::assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        self::assertCount(2, $body['items']);
        self::assertSame('email', $body['items'][0]['channel_type']);
        self::assertSame('Email Alert', $body['items'][0]['label']);
        self::assertTrue($body['items'][0]['is_enabled']);
    }

    // ── POST /api/v1/notification-channels ───────────────────────────────────

    public function testCreateReturns201WithChannel(): void
    {
        $response = $this->request('POST', '/api/v1/notification-channels', [
            'channel_type' => 'slack',
            'label'        => 'Slack Alerts',
            'is_enabled'   => true,
            'config'       => ['webhook_url' => 'https://hooks.slack.com/xxx'],
        ]);

        self::assertSame(201, $response->getStatusCode());
        $body = $this->json($response);
        self::assertSame(1, $body['id']);
        self::assertSame('slack', $body['channel_type']);
        self::assertSame('Slack Alerts', $body['label']);
        self::assertTrue($body['is_enabled']);
    }

    public function testCreateWithoutChannelTypeReturns422(): void
    {
        $response = $this->request('POST', '/api/v1/notification-channels', [
            'label' => 'No type',
        ]);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testCreateWithoutLabelReturns422(): void
    {
        $response = $this->request('POST', '/api/v1/notification-channels', [
            'channel_type' => 'slack',
        ]);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testCreateWithInvalidChannelTypeReturns422(): void
    {
        $response = $this->request('POST', '/api/v1/notification-channels', [
            'channel_type' => 'telegram',
            'label'        => 'Telegram',
        ]);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testCreateAllValidChannelTypes(): void
    {
        foreach (['email', 'slack', 'discord', 'chatwork', 'webhook'] as $type) {
            $response = $this->request('POST', '/api/v1/notification-channels', [
                'channel_type' => $type,
                'label'        => "Test {$type}",
            ]);
            self::assertSame(201, $response->getStatusCode(), "Failed for type: {$type}");
        }
    }

    // ── PATCH /api/v1/notification-channels/{id} ──────────────────────────────

    public function testUpdateReturnsSuccessTrue(): void
    {
        $created = $this->repository->create('email', 'Old', true, ['to_address' => 'old@example.com']);

        $response = $this->request('PATCH', "/api/v1/notification-channels/{$created->id}", [
            'label'      => 'New',
            'is_enabled' => false,
            'config'     => ['to_address' => 'new@example.com'],
        ]);

        self::assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        self::assertTrue($body['success']);

        $updated = $this->repository->findById($created->id);
        self::assertNotNull($updated);
        self::assertSame('New', $updated->label);
        self::assertFalse($updated->isEnabled);
    }

    public function testUpdateWithoutLabelReturns422(): void
    {
        $created = $this->repository->create('slack', 'Old', true, []);

        $response = $this->request('PATCH', "/api/v1/notification-channels/{$created->id}", [
            'is_enabled' => false,
        ]);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testUpdateNonExistentChannelReturns404(): void
    {
        $response = $this->request('PATCH', '/api/v1/notification-channels/999', [
            'label'      => 'Ghost',
            'is_enabled' => true,
        ]);

        self::assertSame(404, $response->getStatusCode());
    }

    // ── DELETE /api/v1/notification-channels/{id} ─────────────────────────────

    public function testDeleteReturns204(): void
    {
        $created = $this->repository->create('webhook', 'Webhook', true, ['url' => 'https://example.com']);

        $response = $this->request('DELETE', "/api/v1/notification-channels/{$created->id}");

        self::assertSame(204, $response->getStatusCode());
        self::assertNull($this->repository->findById($created->id));
    }

    public function testDeleteNonExistentChannelReturns404(): void
    {
        $response = $this->request('DELETE', '/api/v1/notification-channels/999');

        self::assertSame(404, $response->getStatusCode());
    }

    // ── POST /api/v1/notification-channels/{id}/test ──────────────────────────

    public function testTestChannelReturns200WithSentTrue(): void
    {
        $created = $this->repository->create('slack', 'Slack', true, ['webhook_url' => 'https://hooks.slack.com/xxx']);

        $response = $this->request('POST', "/api/v1/notification-channels/{$created->id}/test");

        self::assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        self::assertTrue($body['sent']);
    }

    public function testTestNonExistentChannelReturns404(): void
    {
        $response = $this->request('POST', '/api/v1/notification-channels/999/test');

        self::assertSame(404, $response->getStatusCode());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param array<string,mixed>|null $body
     */
    private function request(string $method, string $path, array|null $body = null): ResponseInterface
    {
        $request = $this->factory->createServerRequest($method, $path);

        if ($body !== null) {
            $stream = $this->factory->createStream(json_encode($body, JSON_THROW_ON_ERROR));
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($stream);
        }

        return $this->application->handle($request);
    }

    /** @return array<string,mixed> */
    private function json(ResponseInterface $response): array
    {
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }
}
