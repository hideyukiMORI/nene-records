<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityType;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use NeNeRecords\EntityType\CreateEntityTypeHandler;
use NeNeRecords\EntityType\CreateEntityTypeUseCase;
use NeNeRecords\EntityType\DeleteEntityTypeHandler;
use NeNeRecords\EntityType\DeleteEntityTypeUseCase;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\EntityTypeHasEntitiesExceptionHandler;
use NeNeRecords\EntityType\EntityTypeNotFoundExceptionHandler;
use NeNeRecords\EntityType\EntityTypeRouteRegistrar;
use NeNeRecords\EntityType\EntityTypeSlugConflictExceptionHandler;
use NeNeRecords\EntityType\GetEntityTypeByIdHandler;
use NeNeRecords\EntityType\GetEntityTypeByIdUseCase;
use NeNeRecords\EntityType\ListEntityTypesHandler;
use NeNeRecords\EntityType\ListEntityTypesUseCase;
use NeNeRecords\EntityType\ReorderEntityTypesHandler;
use NeNeRecords\EntityType\ReorderEntityTypesUseCase;
use NeNeRecords\EntityType\UpdateEntityTypeHandler;
use NeNeRecords\EntityType\UpdateEntityTypeUseCase;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityArchive\InMemoryEntityArchiveRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class EntityTypeHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryEntityTypeRepository $repository;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->repository = new InMemoryEntityTypeRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $entityRepository = new InMemoryEntityRepository();
        $archiveRepository = new InMemoryEntityArchiveRepository();

        $registrar = new EntityTypeRouteRegistrar(
            new GetEntityTypeByIdHandler(new GetEntityTypeByIdUseCase($this->repository), $jsonResponse),
            new CreateEntityTypeHandler(new CreateEntityTypeUseCase($this->repository), $jsonResponse),
            new UpdateEntityTypeHandler(new UpdateEntityTypeUseCase($this->repository), $jsonResponse),
            new DeleteEntityTypeHandler(new DeleteEntityTypeUseCase($this->repository, $entityRepository, $archiveRepository), $this->factory),
            new ListEntityTypesHandler(new ListEntityTypesUseCase($this->repository), $jsonResponse),
            new ReorderEntityTypesHandler(new ReorderEntityTypesUseCase($this->repository), $this->factory),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new EntityTypeNotFoundExceptionHandler($problemDetails),
                new EntityTypeHasEntitiesExceptionHandler($problemDetails),
                new EntityTypeSlugConflictExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testPostEntityTypeCreatesTypeAndReturns201WithLocation(): void
    {
        $body = $this->factory->createStream(json_encode(['name' => 'Article', 'slug' => 'article'], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entity-types')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertStringStartsWith('/api/v1/entity-types/', $response->getHeaderLine('Location'));
        self::assertSame('Article', $payload['name']);
        self::assertSame('article', $payload['slug']);
        self::assertIsInt($payload['id']);
    }

    public function testPostDuplicateSlugReturns409WithConflictProblemType(): void
    {
        $this->repository->save(new EntityType(name: 'Existing', slug: 'dup-slug'));

        $body = $this->factory->createStream(json_encode(['name' => 'Try again', 'slug' => 'dup-slug'], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entity-types')->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(409, $response->getStatusCode());
        self::assertStringEndsWith('conflict', (string) $payload['type']);
        self::assertSame('Conflict', $payload['title']);
    }

    public function testGetEntityTypeByIdReturnsEntityType(): void
    {
        $id = $this->repository->save(new EntityType(name: 'Notebook', slug: 'notebook'));

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', "https://example.test/api/v1/entity-types/{$id}"),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($id, $payload['id']);
        self::assertSame('Notebook', $payload['name']);
        self::assertSame('notebook', $payload['slug']);
    }

    public function testGetEntityTypeByIdReturns404WhenAbsent(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/entity-types/404'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(404, $response->getStatusCode());
        self::assertStringEndsWith('not-found', (string) $payload['type']);
    }

    public function testDeleteEntityTypeReturns204(): void
    {
        $id = $this->repository->save(new EntityType(name: 'Temp', slug: 'temp'));

        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/entity-types/{$id}"),
        );

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('', (string) $response->getBody());
        self::assertNull($this->repository->findById($id));
    }

    public function testListEntityTypesReturnsEmptyItems(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/entity-types'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $payload['items']);
        self::assertSame(20, $payload['limit']);
        self::assertSame(0, $payload['offset']);
    }

    public function testReorderEntityTypesPersistsNewOrderAndReturns204(): void
    {
        $first = $this->repository->save(new EntityType(name: 'Posts', slug: 'posts'));
        $second = $this->repository->save(new EntityType(name: 'Pages', slug: 'pages'));

        $body = $this->factory->createStream(
            json_encode(['ids' => [$second, $first]], JSON_THROW_ON_ERROR),
        );
        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', 'https://example.test/api/v1/entity-types/reorder')->withBody($body),
        );

        self::assertSame(204, $response->getStatusCode());

        // The list now reflects the new order (Pages before Posts).
        $list = $this->decodeJson(
            $this->application->handle(
                $this->factory->createServerRequest('GET', 'https://example.test/api/v1/entity-types'),
            ),
        );
        $slugs = array_map(static fn (array $item): string => (string) $item['slug'], $list['items']);
        self::assertSame(['pages', 'posts'], $slugs);
    }

    public function testReorderEntityTypesWithNonArrayIdsReturns422(): void
    {
        $body = $this->factory->createStream(json_encode(['ids' => 'nope'], JSON_THROW_ON_ERROR));
        $response = $this->application->handle(
            $this->factory->createServerRequest('PUT', 'https://example.test/api/v1/entity-types/reorder')->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
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
