<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Webhook;

use Nene2\Http\UtcClock;
use NeNeRecords\Webhook\CreateWebhookInput;
use NeNeRecords\Webhook\CreateWebhookUseCase;
use NeNeRecords\Webhook\DeleteWebhookInput;
use NeNeRecords\Webhook\DeleteWebhookUseCase;
use NeNeRecords\Webhook\GetWebhookByIdInput;
use NeNeRecords\Webhook\GetWebhookByIdUseCase;
use NeNeRecords\Webhook\UpdateWebhookInput;
use NeNeRecords\Webhook\UpdateWebhookUseCase;
use NeNeRecords\Webhook\WebhookNotFoundException;
use PHPUnit\Framework\TestCase;

final class CreateWebhookUseCaseTest extends TestCase
{
    public function testCreatesWebhookAndReturnsCorrectOutput(): void
    {
        $webhooks = new InMemoryWebhookRepository();
        $useCase = new CreateWebhookUseCase($webhooks, new UtcClock());

        $output = $useCase->execute(new CreateWebhookInput(
            url: 'https://example.com/hook',
            events: ['entity.created'],
            entityTypeId: 5,
            secret: 'mysecret',
            isActive: true,
        ));

        self::assertSame(1, $output->id);
        self::assertSame('https://example.com/hook', $output->url);
        self::assertSame(['entity.created'], $output->events);
        self::assertSame(5, $output->entityTypeId);
        self::assertSame('mysecret', $output->secret);
        self::assertSame(true, $output->isActive);
        self::assertNotSame('', $output->createdAt);
        self::assertNotSame('', $output->updatedAt);
    }

    public function testCreatesWebhookWithNullableFields(): void
    {
        $webhooks = new InMemoryWebhookRepository();
        $useCase = new CreateWebhookUseCase($webhooks, new UtcClock());

        $output = $useCase->execute(new CreateWebhookInput(
            url: 'https://example.com/hook',
            events: [],
            entityTypeId: null,
            secret: null,
            isActive: false,
        ));

        self::assertSame(1, $output->id);
        self::assertNull($output->entityTypeId);
        self::assertNull($output->secret);
        self::assertSame(false, $output->isActive);
    }

    public function testAssignsSequentialIds(): void
    {
        $webhooks = new InMemoryWebhookRepository();
        $useCase = new CreateWebhookUseCase($webhooks, new UtcClock());

        $first = $useCase->execute(new CreateWebhookInput(
            url: 'https://example.com/hook1',
            events: [],
            entityTypeId: null,
            secret: null,
            isActive: true,
        ));
        $second = $useCase->execute(new CreateWebhookInput(
            url: 'https://example.com/hook2',
            events: [],
            entityTypeId: null,
            secret: null,
            isActive: true,
        ));

        self::assertSame(1, $first->id);
        self::assertSame(2, $second->id);
    }
}

final class UpdateWebhookUseCaseTest extends TestCase
{
    public function testUpdatesWebhookAndReturnsOutput(): void
    {
        $webhooks = new InMemoryWebhookRepository();
        $createUseCase = new CreateWebhookUseCase($webhooks, new UtcClock());
        $createUseCase->execute(new CreateWebhookInput(
            url: 'https://example.com/hook',
            events: ['entity.created'],
            entityTypeId: null,
            secret: null,
            isActive: true,
        ));

        $updateUseCase = new UpdateWebhookUseCase($webhooks, new UtcClock());
        $output = $updateUseCase->execute(new UpdateWebhookInput(
            id: 1,
            url: 'https://example.com/hook-updated',
            events: ['entity.deleted'],
            entityTypeId: 3,
            secret: 'newsecret',
            isActive: false,
        ));

        self::assertSame(1, $output->id);
        self::assertSame('https://example.com/hook-updated', $output->url);
        self::assertSame(['entity.deleted'], $output->events);
        self::assertSame(3, $output->entityTypeId);
        self::assertSame('newsecret', $output->secret);
        self::assertSame(false, $output->isActive);
    }

    public function testNullSecretKeepsExistingSecret(): void
    {
        $webhooks = new InMemoryWebhookRepository();
        $createUseCase = new CreateWebhookUseCase($webhooks, new UtcClock());
        $createUseCase->execute(new CreateWebhookInput(
            url: 'https://example.com/hook',
            events: ['entity.created'],
            entityTypeId: null,
            secret: 'existing-secret',
            isActive: true,
        ));

        $updateUseCase = new UpdateWebhookUseCase($webhooks, new UtcClock());
        $output = $updateUseCase->execute(new UpdateWebhookInput(
            id: 1,
            url: 'https://example.com/hook-updated',
            events: ['entity.updated'],
            entityTypeId: null,
            secret: null,
            isActive: true,
        ));

        // Write-only update (#836): an omitted secret keeps the stored value.
        self::assertSame('existing-secret', $output->secret);
        self::assertSame('existing-secret', $webhooks->findById(1)?->secret);
    }

    public function testNonNullSecretReplacesExistingSecret(): void
    {
        $webhooks = new InMemoryWebhookRepository();
        $createUseCase = new CreateWebhookUseCase($webhooks, new UtcClock());
        $createUseCase->execute(new CreateWebhookInput(
            url: 'https://example.com/hook',
            events: ['entity.created'],
            entityTypeId: null,
            secret: 'old-secret',
            isActive: true,
        ));

        $updateUseCase = new UpdateWebhookUseCase($webhooks, new UtcClock());
        $output = $updateUseCase->execute(new UpdateWebhookInput(
            id: 1,
            url: 'https://example.com/hook',
            events: ['entity.created'],
            entityTypeId: null,
            secret: 'new-secret',
            isActive: true,
        ));

        self::assertSame('new-secret', $output->secret);
        self::assertSame('new-secret', $webhooks->findById(1)?->secret);
    }

    public function testThrowsWebhookNotFoundExceptionIfNotFound(): void
    {
        $webhooks = new InMemoryWebhookRepository();
        $useCase = new UpdateWebhookUseCase($webhooks, new UtcClock());

        $this->expectException(WebhookNotFoundException::class);

        $useCase->execute(new UpdateWebhookInput(
            id: 99,
            url: 'https://ghost.example.com',
            events: [],
            entityTypeId: null,
            secret: null,
            isActive: false,
        ));
    }
}

final class DeleteWebhookUseCaseTest extends TestCase
{
    public function testDeletesWebhook(): void
    {
        $webhooks = new InMemoryWebhookRepository();
        $createUseCase = new CreateWebhookUseCase($webhooks, new UtcClock());
        $createUseCase->execute(new CreateWebhookInput(
            url: 'https://example.com/hook',
            events: [],
            entityTypeId: null,
            secret: null,
            isActive: true,
        ));

        $deleteUseCase = new DeleteWebhookUseCase($webhooks);
        $deleteUseCase->execute(new DeleteWebhookInput(id: 1));

        self::assertNull($webhooks->findById(1));
    }

    public function testThrowsWebhookNotFoundExceptionIfNotFound(): void
    {
        $webhooks = new InMemoryWebhookRepository();
        $useCase = new DeleteWebhookUseCase($webhooks);

        $this->expectException(WebhookNotFoundException::class);

        $useCase->execute(new DeleteWebhookInput(id: 99));
    }
}

final class GetWebhookByIdUseCaseTest extends TestCase
{
    public function testReturnsWebhook(): void
    {
        $webhooks = new InMemoryWebhookRepository();
        $createUseCase = new CreateWebhookUseCase($webhooks, new UtcClock());
        $createUseCase->execute(new CreateWebhookInput(
            url: 'https://example.com/hook',
            events: ['entity.created'],
            entityTypeId: 7,
            secret: 'secret',
            isActive: true,
        ));

        $getUseCase = new GetWebhookByIdUseCase($webhooks);
        $output = $getUseCase->execute(new GetWebhookByIdInput(id: 1));

        self::assertSame(1, $output->webhook->id);
        self::assertSame('https://example.com/hook', $output->webhook->url);
        self::assertSame(['entity.created'], $output->webhook->events);
        self::assertSame(7, $output->webhook->entityTypeId);
        self::assertSame('secret', $output->webhook->secret);
        self::assertSame(true, $output->webhook->isActive);
    }

    public function testThrowsWebhookNotFoundExceptionIfNotFound(): void
    {
        $webhooks = new InMemoryWebhookRepository();
        $useCase = new GetWebhookByIdUseCase($webhooks);

        $this->expectException(WebhookNotFoundException::class);

        $useCase->execute(new GetWebhookByIdInput(id: 99));
    }
}
