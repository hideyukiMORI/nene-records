<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Webhook;

use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Webhook\ProcessWebhookDeliveriesHandler;
use NeNeRecords\Webhook\WebhookDeliveryProcessor;
use NeNeRecords\Webhook\WebhookSendResult;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class ProcessWebhookDeliveriesHandlerTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryWebhookDeliveryRepository $deliveries;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Psr17Factory();
        $this->deliveries = new InMemoryWebhookDeliveryRepository();
    }

    private function enqueueDue(): int
    {
        // nextAttemptAt in the distant past so it is due against the real clock.
        return $this->deliveries->enqueue(
            1,
            'entity.created',
            1,
            42,
            'https://example.test/hook',
            'secret',
            '{"event":"entity.created"}',
            5,
            1000,
        );
    }

    private function handler(WebhookSendResult $result): ProcessWebhookDeliveriesHandler
    {
        return new ProcessWebhookDeliveriesHandler(
            new WebhookDeliveryProcessor($this->deliveries, new FakeWebhookSender($result)),
            new JsonResponseFactory($this->factory, $this->factory),
        );
    }

    public function testDrainsDueDeliveriesAndReturnsSummary(): void
    {
        $this->enqueueDue();
        $this->enqueueDue();

        $request = $this->factory->createServerRequest('POST', 'https://example.test/api/v1/webhooks/process-deliveries');
        $response = $this->handler(WebhookSendResult::ok(200))->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true);
        self::assertSame(
            ['processed' => 2, 'delivered' => 2, 'retried' => 0, 'failed' => 0],
            $payload,
        );
    }

    public function testLimitQueryParamCapsProcessedDeliveries(): void
    {
        $this->enqueueDue();
        $this->enqueueDue();
        $this->enqueueDue();

        $request = $this->factory
            ->createServerRequest('POST', 'https://example.test/api/v1/webhooks/process-deliveries')
            ->withQueryParams(['limit' => '2']);
        $response = $this->handler(WebhookSendResult::ok(200))->handle($request);

        $payload = json_decode((string) $response->getBody(), true);
        self::assertIsArray($payload);
        self::assertSame(2, $payload['processed']);
        self::assertSame(2, $payload['delivered']);
    }

    public function testNoDueDeliveriesReturnsZeroedSummary(): void
    {
        $request = $this->factory->createServerRequest('POST', 'https://example.test/api/v1/webhooks/process-deliveries');
        $response = $this->handler(WebhookSendResult::ok(200))->handle($request);

        $payload = json_decode((string) $response->getBody(), true);
        self::assertSame(
            ['processed' => 0, 'delivered' => 0, 'retried' => 0, 'failed' => 0],
            $payload,
        );
    }
}
