<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use DateTimeImmutable;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Entity\CreateEntityHandler;
use NeNeRecords\Entity\CreateEntityUseCase;
use NeNeRecords\Entity\DeleteEntityHandler;
use NeNeRecords\Entity\DeleteEntityUseCase;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityNotFoundExceptionHandler;
use NeNeRecords\Entity\EntityRouteRegistrar;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\Entity\ExcerptResolver;
use NeNeRecords\Entity\ExportEntitiesHandler;
use NeNeRecords\Entity\GetEntityByIdHandler;
use NeNeRecords\Entity\GetEntityByIdUseCase;
use NeNeRecords\Entity\ListEntitiesHandler;
use NeNeRecords\Entity\ListEntitiesUseCase;
use NeNeRecords\Entity\ListEntityRevisionsHandler;
use NeNeRecords\Entity\ListEntityRevisionsUseCase;
use NeNeRecords\Entity\ProcessScheduledPublishHandler;
use NeNeRecords\Entity\ProcessScheduledPublishUseCase;
use NeNeRecords\Entity\ScheduleEntityHandler;
use NeNeRecords\Entity\ScheduleEntityUseCase;
use NeNeRecords\Entity\UnscheduleEntityHandler;
use NeNeRecords\Entity\UnscheduleEntityUseCase;
use NeNeRecords\Entity\UpdateEntityHandler;
use NeNeRecords\Entity\UpdateEntityUseCase;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\EntityTypeNotFoundExceptionHandler;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\Setting\InMemorySettingRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

final class ScheduledPublishHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryEntityTypeRepository $entityTypes;
    private InMemoryEntityRepository $entities;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->entityTypes = new InMemoryEntityTypeRepository();
        $this->entities = new InMemoryEntityRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new EntityRouteRegistrar(
            new GetEntityByIdHandler(new GetEntityByIdUseCase($this->entities), $jsonResponse),
            new CreateEntityHandler(new CreateEntityUseCase($this->entities, $this->entityTypes), $jsonResponse),
            new UpdateEntityHandler(new UpdateEntityUseCase($this->entities, $this->entityTypes), $jsonResponse),
            new DeleteEntityHandler(new DeleteEntityUseCase($this->entities), $this->factory),
            new ListEntitiesHandler(new ListEntitiesUseCase($this->entities), $jsonResponse, new ExcerptResolver(new InMemoryTextFieldRepository(), new InMemorySettingRepository())),
            new ListEntityRevisionsHandler(new ListEntityRevisionsUseCase($this->entities), $jsonResponse),
            new ExportEntitiesHandler($this->entities, new InMemoryTextFieldRepository(), $this->factory),
            new ScheduleEntityHandler(new ScheduleEntityUseCase($this->entities), $jsonResponse),
            new UnscheduleEntityHandler(new UnscheduleEntityUseCase($this->entities), $this->factory),
            new ProcessScheduledPublishHandler(new ProcessScheduledPublishUseCase($this->entities), $jsonResponse),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new EntityTypeNotFoundExceptionHandler($problemDetails),
                new EntityNotFoundExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testScheduleEntitySetsStatusToScheduledAndReturns200(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Post', slug: 'post'));
        $entityId = $this->entities->save(new Entity(id: null, entityTypeId: $typeId));

        $futureDate = (new DateTimeImmutable('+1 day'))->format(DATE_ATOM);
        $body = $this->factory->createStream(json_encode(['scheduled_at' => $futureDate], JSON_THROW_ON_ERROR));

        $request = $this->factory->createServerRequest('POST', "https://example.test/api/v1/entities/{$entityId}/schedule")
            ->withBody($body)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->application->handle($request);

        self::assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        self::assertIsArray($data);
        self::assertSame($entityId, $data['id']);
        self::assertSame('scheduled', $data['status']);
        self::assertNotNull($data['scheduled_at']);
    }

    public function testScheduleEntityWithPastDateReturns422(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Post', slug: 'post'));
        $entityId = $this->entities->save(new Entity(id: null, entityTypeId: $typeId));

        $pastDate = (new DateTimeImmutable('-1 day'))->format(DATE_ATOM);
        $body = $this->factory->createStream(json_encode(['scheduled_at' => $pastDate], JSON_THROW_ON_ERROR));

        $request = $this->factory->createServerRequest('POST', "https://example.test/api/v1/entities/{$entityId}/schedule")
            ->withBody($body)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->application->handle($request);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testScheduleEntityWithMissingDateReturns422(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Post', slug: 'post'));
        $entityId = $this->entities->save(new Entity(id: null, entityTypeId: $typeId));

        $body = $this->factory->createStream('{}');

        $request = $this->factory->createServerRequest('POST', "https://example.test/api/v1/entities/{$entityId}/schedule")
            ->withBody($body)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->application->handle($request);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testScheduleNonExistentEntityReturns404(): void
    {
        $futureDate = (new DateTimeImmutable('+1 day'))->format(DATE_ATOM);
        $body = $this->factory->createStream(json_encode(['scheduled_at' => $futureDate], JSON_THROW_ON_ERROR));

        $request = $this->factory->createServerRequest('POST', '/api/v1/entities/9999/schedule')
            ->withBody($body)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->application->handle($request);

        self::assertSame(404, $response->getStatusCode());
    }

    public function testUnscheduleEntityRevertsToDraftAndReturns204(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Post', slug: 'post'));
        $entityId = $this->entities->save(new Entity(
            id: null,
            entityTypeId: $typeId,
            status: EntityStatus::Scheduled,
            scheduledAt: new DateTimeImmutable('+1 day'),
        ));

        $request = $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/entities/{$entityId}/schedule");

        $response = $this->application->handle($request);

        self::assertSame(204, $response->getStatusCode());

        $entity = $this->entities->findById($entityId);
        self::assertNotNull($entity);
        self::assertSame(EntityStatus::Draft, $entity->status);
        self::assertNull($entity->scheduledAt);
    }

    public function testProcessScheduledPublishesAllDueEntities(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Post', slug: 'post'));

        // Past scheduled_at → should be published
        $dueId = $this->entities->save(new Entity(
            id: null,
            entityTypeId: $typeId,
            status: EntityStatus::Scheduled,
            scheduledAt: new DateTimeImmutable('-5 minutes'),
        ));

        // Future scheduled_at → should NOT be published
        $futureId = $this->entities->save(new Entity(
            id: null,
            entityTypeId: $typeId,
            status: EntityStatus::Scheduled,
            scheduledAt: new DateTimeImmutable('+1 day'),
        ));

        $request = $this->factory->createServerRequest('POST', '/api/v1/entities/process-scheduled');
        $response = $this->application->handle($request);

        self::assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        self::assertIsArray($data);
        self::assertSame(1, $data['published_count']);
        self::assertContains($dueId, $data['published_ids']);
        self::assertNotContains($futureId, $data['published_ids']);

        // Verify the due entity is now published
        $published = $this->entities->findById($dueId);
        self::assertNotNull($published);
        self::assertSame(EntityStatus::Published, $published->status);
        self::assertNull($published->scheduledAt);

        // Verify the future entity is still scheduled
        $stillScheduled = $this->entities->findById($futureId);
        self::assertNotNull($stillScheduled);
        self::assertSame(EntityStatus::Scheduled, $stillScheduled->status);
    }

    public function testGetEntityIncludesScheduledAtField(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Post', slug: 'post'));
        $scheduledAt = new DateTimeImmutable('+1 day');
        $entityId = $this->entities->save(new Entity(
            id: null,
            entityTypeId: $typeId,
            status: EntityStatus::Scheduled,
            scheduledAt: $scheduledAt,
        ));

        $request = $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$entityId}");
        $response = $this->application->handle($request);

        self::assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        self::assertIsArray($data);
        self::assertSame('scheduled', $data['status']);
        self::assertNotNull($data['scheduled_at']);
    }
}
