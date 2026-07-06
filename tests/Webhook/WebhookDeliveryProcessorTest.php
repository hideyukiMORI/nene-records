<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Webhook;

use Nene2\Http\UtcClock;
use NeNeRecords\Webhook\WebhookDeliveryProcessor;
use NeNeRecords\Webhook\WebhookSendResult;
use PHPUnit\Framework\TestCase;

final class WebhookDeliveryProcessorTest extends TestCase
{
    private InMemoryWebhookDeliveryRepository $deliveries;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deliveries = new InMemoryWebhookDeliveryRepository();
    }

    private function enqueue(int $maxAttempts = 5, int $nextAttemptAt = 1000): int
    {
        return $this->deliveries->enqueue(
            1,
            'entity.created',
            1,
            42,
            'https://example.test/hook',
            'secret',
            '{"event":"entity.created"}',
            $maxAttempts,
            $nextAttemptAt,
        );
    }

    public function testSuccessfulDeliveryIsMarkedDelivered(): void
    {
        $id = $this->enqueue();
        $sender = new FakeWebhookSender(WebhookSendResult::ok(200));
        $processor = new WebhookDeliveryProcessor($this->deliveries, $sender, new UtcClock());

        $summary = $processor->process(50, 2000);

        self::assertSame(['processed' => 1, 'delivered' => 1, 'retried' => 0, 'failed' => 0], $summary);
        self::assertSame('delivered', $this->deliveries->rows[$id]->status);
        self::assertSame(1, $this->deliveries->rows[$id]->attempts);
        self::assertSame(200, $this->deliveries->rows[$id]->responseStatus);
        self::assertCount(1, $sender->calls);
    }

    public function testFailedDeliveryIsRescheduledWithBackoff(): void
    {
        $id = $this->enqueue(maxAttempts: 5);
        $sender = new FakeWebhookSender(WebhookSendResult::failure('timeout'));
        $processor = new WebhookDeliveryProcessor($this->deliveries, $sender, new UtcClock());

        $now = 2000;
        $summary = $processor->process(50, $now);

        self::assertSame(['processed' => 1, 'delivered' => 0, 'retried' => 1, 'failed' => 0], $summary);

        $row = $this->deliveries->rows[$id];
        self::assertSame('pending', $row->status);
        self::assertSame(1, $row->attempts);
        self::assertSame('timeout', $row->lastError);
        // First backoff is 60s.
        self::assertSame($now + 60, $row->nextAttemptAt);
    }

    public function testDeliveryIsMarkedFailedOnceAttemptsExhausted(): void
    {
        // maxAttempts=1 → the first failure exhausts the budget.
        $id = $this->enqueue(maxAttempts: 1);
        $sender = new FakeWebhookSender(WebhookSendResult::failure('connection refused'));
        $processor = new WebhookDeliveryProcessor($this->deliveries, $sender, new UtcClock());

        $summary = $processor->process(50, 2000);

        self::assertSame(['processed' => 1, 'delivered' => 0, 'retried' => 0, 'failed' => 1], $summary);
        self::assertSame('failed', $this->deliveries->rows[$id]->status);
        self::assertSame(1, $this->deliveries->rows[$id]->attempts);
        self::assertSame('connection refused', $this->deliveries->rows[$id]->lastError);
    }

    public function testNotYetDueDeliveriesAreSkipped(): void
    {
        $this->enqueue(nextAttemptAt: 5000);
        $sender = new FakeWebhookSender(WebhookSendResult::ok(200));
        $processor = new WebhookDeliveryProcessor($this->deliveries, $sender, new UtcClock());

        $summary = $processor->process(50, 2000);

        self::assertSame(0, $summary['processed']);
        self::assertCount(0, $sender->calls);
    }
}
