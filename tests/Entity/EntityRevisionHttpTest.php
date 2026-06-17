<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\Entity\CreateEntityHandler;
use NeNeRecords\Entity\CreateEntityUseCase;
use NeNeRecords\Entity\DeleteEntityHandler;
use NeNeRecords\Entity\DeleteEntityUseCase;
use NeNeRecords\Entity\EntityNotFoundExceptionHandler;
use NeNeRecords\Entity\EntityRouteRegistrar;
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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class EntityRevisionHttpTest extends TestCase
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

        $createUseCase = new CreateEntityUseCase($this->entities, $this->entityTypes);

        $registrar = new EntityRouteRegistrar(
            new GetEntityByIdHandler(new GetEntityByIdUseCase($this->entities), $jsonResponse),
            new CreateEntityHandler($createUseCase, $jsonResponse),
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

    public function testCreateEntityProducesCreatedRevision(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Doc', slug: 'doc'));

        $body = $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR));
        $createResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($body),
        );
        $created = $this->decodeJson($createResponse);
        $entityId = (int) $created['id'];

        self::assertSame(201, $createResponse->getStatusCode());

        $revisionsResponse = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$entityId}/revisions"),
        );
        $payload = $this->decodeJson($revisionsResponse);

        self::assertSame(200, $revisionsResponse->getStatusCode());
        self::assertCount(1, $payload['items']);
        self::assertSame('created', $payload['items'][0]['action']);
        self::assertSame('draft', $payload['items'][0]['status']);
        self::assertNull($payload['items'][0]['previous_status']);
    }

    public function testUpdateEntityProducesUpdatedRevision(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Doc', slug: 'doc'));

        $body = $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR));
        $createResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($body),
        );
        $created = $this->decodeJson($createResponse);
        $entityId = (int) $created['id'];

        $updateBody = $this->factory->createStream(json_encode([
            'entity_type_id' => $typeId,
            'status' => 'published',
            'slug' => 'my-doc',
        ], JSON_THROW_ON_ERROR));
        $this->application->handle(
            $this->factory->createServerRequest('PUT', "https://example.test/api/v1/entities/{$entityId}")->withBody($updateBody),
        );

        $revisionsResponse = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entities/{$entityId}/revisions"),
        );
        $payload = $this->decodeJson($revisionsResponse);

        self::assertSame(200, $revisionsResponse->getStatusCode());
        // Newest first: updated, created
        self::assertCount(2, $payload['items']);
        self::assertSame('updated', $payload['items'][0]['action']);
        self::assertSame('published', $payload['items'][0]['status']);
        self::assertSame('draft', $payload['items'][0]['previous_status']);
        self::assertSame('my-doc', $payload['items'][0]['slug']);
    }

    public function testDeleteEntityProducesDeletedRevision(): void
    {
        $typeId = $this->entityTypes->save(new EntityType(name: 'Doc', slug: 'doc'));

        $body = $this->factory->createStream(json_encode(['entity_type_id' => $typeId], JSON_THROW_ON_ERROR));
        $createResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities')->withBody($body),
        );
        $created = $this->decodeJson($createResponse);
        $entityId = (int) $created['id'];

        $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/entities/{$entityId}"),
        );

        // After soft delete the entity is gone, but revisions were recorded before deletion.
        // We check the in-memory store directly.
        $revisions = $this->entities->findRevisionsByEntityId($entityId, 20, 0);

        self::assertCount(2, $revisions);
        self::assertSame('deleted', $revisions[0]->action->value);
    }

    public function testGetRevisionsForNonExistentEntityReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/entities/9999/revisions'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    /** @return array<string, mixed> */
    private function decodeJson(ResponseInterface $response): array
    {
        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
