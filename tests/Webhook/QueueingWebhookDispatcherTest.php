<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Webhook;

use NeNeRecords\Webhook\QueueingWebhookDispatcher;
use NeNeRecords\Webhook\Webhook;
use PHPUnit\Framework\TestCase;

final class QueueingWebhookDispatcherTest extends TestCase
{
    private InMemoryWebhookRepository $webhooks;
    private InMemoryWebhookDeliveryRepository $deliveries;
    private QueueingWebhookDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhooks = new InMemoryWebhookRepository();
        $this->deliveries = new InMemoryWebhookDeliveryRepository();
        $this->dispatcher = new QueueingWebhookDispatcher($this->webhooks, $this->deliveries);
    }

    private function seedWebhook(string $url, string $event, ?int $entityTypeId, ?string $secret = null): void
    {
        $this->webhooks->save(new Webhook(
            id: null,
            url: $url,
            events: [$event],
            entityTypeId: $entityTypeId,
            secret: $secret,
            isActive: true,
            createdAt: '',
            updatedAt: '',
        ));
    }

    public function testEnqueuesOneDeliveryPerMatchingWebhook(): void
    {
        $this->seedWebhook('https://a.example/hook', 'entity.created', 1, 'sek');
        $this->seedWebhook('https://b.example/hook', 'entity.created', null);
        // Non-matching: different event.
        $this->seedWebhook('https://c.example/hook', 'entity.deleted', 1);

        $this->dispatcher->dispatch('entity.created', 1, 42);

        self::assertCount(2, $this->deliveries->rows);

        $row = $this->deliveries->rows[1];
        self::assertSame('https://a.example/hook', $row->targetUrl);
        self::assertSame('sek', $row->secret);
        self::assertSame('entity.created', $row->event);
        self::assertSame(1, $row->entityTypeId);
        self::assertSame(42, $row->entityId);
        self::assertSame('pending', $row->status);
        self::assertSame(0, $row->attempts);
        self::assertStringContainsString('"entity_id":42', $row->payload);
    }

    public function testNoMatchingWebhooksEnqueuesNothing(): void
    {
        $this->seedWebhook('https://a.example/hook', 'entity.updated', 1);

        $this->dispatcher->dispatch('entity.created', 1, 7);

        self::assertSame([], $this->deliveries->rows);
    }
}
