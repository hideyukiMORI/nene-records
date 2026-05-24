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
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityNotFoundExceptionHandler;
use NeNeRecords\Entity\EntityRouteRegistrar;
use NeNeRecords\Entity\GetEntityByIdHandler;
use NeNeRecords\Entity\GetEntityByIdUseCase;
use NeNeRecords\Entity\ListEntitiesHandler;
use NeNeRecords\Entity\ListEntitiesUseCase;
use NeNeRecords\Entity\UpdateEntityHandler;
use NeNeRecords\Entity\UpdateEntityUseCase;
use NeNeRecords\EntityType\EntityTypeNotFoundExceptionHandler;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class EntityFilterHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryEntityRepository $entities;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1),
            new Entity(id: 2, entityTypeId: 1),
            new Entity(id: 3, entityTypeId: 2),
        ]);
        $this->entities->setTagSlugsForEntity(1, ['featured']);
        $this->entities->setTagSlugsForEntity(2, ['draft']);
        $this->entities->setTagSlugsForEntity(3, ['featured']);

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);
        $entityTypes = new InMemoryEntityTypeRepository();

        $registrar = new EntityRouteRegistrar(
            new GetEntityByIdHandler(new GetEntityByIdUseCase($this->entities), $jsonResponse),
            new CreateEntityHandler(new CreateEntityUseCase($this->entities, $entityTypes), $jsonResponse),
            new UpdateEntityHandler(new UpdateEntityUseCase($this->entities, $entityTypes), $jsonResponse),
            new DeleteEntityHandler(new DeleteEntityUseCase($this->entities), $this->factory),
            new ListEntitiesHandler(new ListEntitiesUseCase($this->entities), $jsonResponse),
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

    public function testListEntitiesFilteredByEntityTypeId(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/entities?entity_type_id=2'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(1, $payload['total']);
        self::assertCount(1, $payload['items']);
        self::assertSame(3, $payload['items'][0]['id']);
    }

    public function testListEntitiesFilteredByTagSlugsUsesOrSemantics(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/entities?tags=featured,draft'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(3, $payload['total']);
        self::assertCount(3, $payload['items']);
    }

    public function testListEntitiesMergesTagAndTagsQueryParameters(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/entities?tag=draft&tags=featured'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(3, $payload['total']);
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
