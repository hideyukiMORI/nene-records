<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Nene2\Http\UtcClock;
use NeNeRecords\Entity\DeleteEntityInput;
use NeNeRecords\Entity\DeleteEntityUseCase;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\Entity\ProcessScheduledPublishUseCase;
use NeNeRecords\Entity\ScheduleEntityInput;
use NeNeRecords\Entity\ScheduleEntityUseCase;
use NeNeRecords\Entity\UnscheduleEntityInput;
use NeNeRecords\Entity\UnscheduleEntityUseCase;
use PHPUnit\Framework\TestCase;

final class EntityScheduleUseCaseTest extends TestCase
{
    // ScheduleEntityUseCase tests

    public function testSchedulesEntitySetsStatusAndScheduledAt(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $scheduledAt = new DateTimeImmutable('+1 hour');
        $useCase = new ScheduleEntityUseCase($entities, new UtcClock());

        $output = $useCase->execute(new ScheduleEntityInput(id: $entityId, scheduledAt: $scheduledAt));

        self::assertSame($entityId, $output->id);
        self::assertSame('scheduled', $output->status);
        self::assertSame($scheduledAt->format(\DateTimeInterface::ATOM), $output->scheduledAtIso);

        $updated = $entities->findById($entityId);
        self::assertNotNull($updated);
        self::assertSame(EntityStatus::Scheduled, $updated->status);
        self::assertSame(
            $scheduledAt->format(\DateTimeInterface::ATOM),
            $updated->scheduledAt?->format(\DateTimeInterface::ATOM),
        );
    }

    public function testSchedulePreservesPermalinkLayoutAndVisibilityOverrides(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(
            id: null,
            entityTypeId: 1,
            slug: 'about',
            permalink: '/company/about',
            layout: 'two-col',
            showComments: false,
            showRelated: false,
        ));

        $useCase = new ScheduleEntityUseCase($entities, new UtcClock());
        $useCase->execute(new ScheduleEntityInput(id: $entityId, scheduledAt: new DateTimeImmutable('+1 hour')));

        // repository::update() is full-replace — scheduling must not wipe these (#776).
        $updated = $entities->findById($entityId);
        self::assertNotNull($updated);
        self::assertSame('/company/about', $updated->permalink);
        self::assertSame('two-col', $updated->layout);
        self::assertFalse($updated->showComments);
        self::assertFalse($updated->showRelated);
    }

    public function testScheduleEntityThrowsEntityNotFoundExceptionWhenEntityMissing(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $useCase = new ScheduleEntityUseCase($entities, new UtcClock());

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new ScheduleEntityInput(id: 999, scheduledAt: new DateTimeImmutable('+1 hour')));
    }

    public function testScheduleEntityThrowsInvalidArgumentExceptionWhenScheduledAtIsInThePast(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $useCase = new ScheduleEntityUseCase($entities, new UtcClock());

        $this->expectException(InvalidArgumentException::class);

        $useCase->execute(new ScheduleEntityInput(id: $entityId, scheduledAt: new DateTimeImmutable('-1 hour')));
    }

    // UnscheduleEntityUseCase tests

    public function testUnschedulesEntitySetsStatusBackToDraftAndClearsScheduledAt(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(
            id: null,
            entityTypeId: 1,
            status: EntityStatus::Scheduled,
            scheduledAt: new DateTimeImmutable('+1 day'),
        ));

        $useCase = new UnscheduleEntityUseCase($entities);

        $useCase->execute(new UnscheduleEntityInput(entityId: $entityId));

        $updated = $entities->findById($entityId);
        self::assertNotNull($updated);
        self::assertSame(EntityStatus::Draft, $updated->status);
        self::assertNull($updated->scheduledAt);
    }

    public function testUnschedulePreservesPermalinkAndLayout(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(
            id: null,
            entityTypeId: 1,
            slug: 'about',
            permalink: '/company/about',
            layout: 'bare',
            status: EntityStatus::Scheduled,
            scheduledAt: new DateTimeImmutable('+1 day'),
        ));

        (new UnscheduleEntityUseCase($entities))->execute(new UnscheduleEntityInput(entityId: $entityId));

        // Full-replace update — cancelling a schedule must not wipe these (#776).
        $updated = $entities->findById($entityId);
        self::assertNotNull($updated);
        self::assertSame('/company/about', $updated->permalink);
        self::assertSame('bare', $updated->layout);
    }

    public function testUnscheduleEntityThrowsEntityNotFoundExceptionWhenEntityMissing(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $useCase = new UnscheduleEntityUseCase($entities);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new UnscheduleEntityInput(entityId: 999));
    }

    // DeleteEntityUseCase tests

    public function testDeleteEntitySoftDeletesEntity(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $useCase = new DeleteEntityUseCase($entities);

        $useCase->execute(new DeleteEntityInput(id: $entityId));

        self::assertNull($entities->findById($entityId));
    }

    public function testDeleteEntityThrowsEntityNotFoundExceptionWhenEntityMissing(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $useCase = new DeleteEntityUseCase($entities);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new DeleteEntityInput(id: 999));
    }

    // ProcessScheduledPublishUseCase tests

    public function testProcessScheduledPublishPublishesDueEntities(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(
            id: null,
            entityTypeId: 1,
            status: EntityStatus::Scheduled,
            scheduledAt: new DateTimeImmutable('-1 minute'),
        ));

        $useCase = new ProcessScheduledPublishUseCase($entities, new UtcClock());

        $output = $useCase->execute();

        self::assertSame([$entityId], $output->publishedIds);

        $published = $entities->findById($entityId);
        self::assertNotNull($published);
        self::assertSame(EntityStatus::Published, $published->status);
        self::assertNull($published->scheduledAt);
    }

    public function testProcessScheduledPublishPreservesPermalinkAndLayout(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(
            id: null,
            entityTypeId: 1,
            slug: 'about',
            permalink: '/company/about',
            layout: 'full',
            status: EntityStatus::Scheduled,
            scheduledAt: new DateTimeImmutable('-1 minute'),
        ));

        (new ProcessScheduledPublishUseCase($entities, new UtcClock()))->execute();

        // Full-replace update — the cron auto-publish must not wipe these (#776).
        $published = $entities->findById($entityId);
        self::assertNotNull($published);
        self::assertSame('/company/about', $published->permalink);
        self::assertSame('full', $published->layout);
    }

    public function testProcessScheduledPublishReturnsEmptyListWhenNoDueEntities(): void
    {
        $entities = new InMemoryEntityRepository([]);

        $useCase = new ProcessScheduledPublishUseCase($entities, new UtcClock());

        $output = $useCase->execute();

        self::assertSame([], $output->publishedIds);
    }

    public function testProcessScheduledPublishDoesNotPublishFutureScheduledEntities(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entities->save(new Entity(
            id: null,
            entityTypeId: 1,
            status: EntityStatus::Scheduled,
            scheduledAt: new DateTimeImmutable('+1 hour'),
        ));

        $useCase = new ProcessScheduledPublishUseCase($entities, new UtcClock());

        $output = $useCase->execute();

        self::assertSame([], $output->publishedIds);
    }
}
