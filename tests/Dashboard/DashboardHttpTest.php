<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Dashboard;

use DateTimeImmutable;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\Http\UtcClock;
use NeNeRecords\Dashboard\DashboardRouteRegistrar;
use NeNeRecords\Dashboard\GetDashboardSummaryHandler;
use NeNeRecords\Dashboard\GetDashboardSummaryUseCase;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\Tests\Analytics\InMemoryAccessLogRepository;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

final class DashboardHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryEntityRepository $entities;
    private InMemoryEntityTypeRepository $entityTypes;
    private InMemoryAccessLogRepository $accessLogs;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->entities = new InMemoryEntityRepository();
        $this->entityTypes = new InMemoryEntityTypeRepository();
        $this->accessLogs = new InMemoryAccessLogRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);

        $useCase = new GetDashboardSummaryUseCase(
            $this->entities,
            $this->entityTypes,
            $this->accessLogs,
            new UtcClock(),
        );

        $registrar = new DashboardRouteRegistrar(
            new GetDashboardSummaryHandler($useCase, $jsonResponse),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testReturnsEmptyDashboardWhenNoData(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/dashboard'),
        );

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $payload['recent_published']);
        self::assertSame(0, $payload['today_access_count']);
        self::assertSame(0, $payload['this_month_access_count']);
        self::assertSame([], $payload['entity_type_summary']);
    }

    public function testReturnsRecentPublishedEntities(): void
    {
        $articleType = new EntityType(name: 'Article', slug: 'article', id: 1);
        $this->entityTypes = new InMemoryEntityTypeRepository([$articleType]);

        $publishedAt = new DateTimeImmutable('2026-05-25T10:00:00+00:00');

        $entity = new Entity(
            id: 1,
            entityTypeId: 1,
            slug: 'hello-world',
            status: EntityStatus::Published,
            publishedAt: $publishedAt,
        );
        $this->entities = new InMemoryEntityRepository([$entity]);

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $useCase = new GetDashboardSummaryUseCase($this->entities, $this->entityTypes, $this->accessLogs, new UtcClock());
        $registrar = new DashboardRouteRegistrar(
            new GetDashboardSummaryHandler($useCase, $jsonResponse),
        );

        $application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [],
            routeRegistrars: [$registrar],
        ))->create();

        $response = $application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/dashboard'),
        );

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $payload['recent_published']);

        $recent = $payload['recent_published'][0];
        self::assertSame(1, $recent['id']);
        self::assertSame(1, $recent['entity_type_id']);
        self::assertSame('Article', $recent['entity_type_name']);
        self::assertSame('article', $recent['entity_type_slug']);
        self::assertSame('hello-world', $recent['slug']);
        self::assertNotNull($recent['published_at']);
    }

    public function testCountsAccessLogs(): void
    {
        $now = new DateTimeImmutable();

        $entry1 = new \NeNeRecords\Analytics\AccessLogEntry(
            requestId: null,
            method: 'GET',
            path: '/api/v1/entities',
            statusCode: 200,
            durationMs: 12.5,
            accessedAt: $now,
        );
        $entry2 = new \NeNeRecords\Analytics\AccessLogEntry(
            requestId: null,
            method: 'GET',
            path: '/api/v1/entities',
            statusCode: 200,
            durationMs: 8.0,
            accessedAt: $now,
        );
        $this->accessLogs->insert($entry1);
        $this->accessLogs->insert($entry2);

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $useCase = new GetDashboardSummaryUseCase($this->entities, $this->entityTypes, $this->accessLogs, new UtcClock());
        $registrar = new DashboardRouteRegistrar(
            new GetDashboardSummaryHandler($useCase, $jsonResponse),
        );

        $application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [],
            routeRegistrars: [$registrar],
        ))->create();

        $response = $application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/dashboard'),
        );

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(2, $payload['today_access_count']);
        self::assertSame(2, $payload['this_month_access_count']);
    }

    public function testEntityTypeSummaryShowsPublishedAndDraftCounts(): void
    {
        $articleType = new EntityType(name: 'Article', slug: 'article', id: 1);
        $this->entityTypes = new InMemoryEntityTypeRepository([$articleType]);

        $published1 = new Entity(id: 1, entityTypeId: 1, slug: 'p1', status: EntityStatus::Published, publishedAt: new DateTimeImmutable());
        $published2 = new Entity(id: 2, entityTypeId: 1, slug: 'p2', status: EntityStatus::Published, publishedAt: new DateTimeImmutable());
        $draft = new Entity(id: 3, entityTypeId: 1, slug: 'd1', status: EntityStatus::Draft);
        $this->entities = new InMemoryEntityRepository([$published1, $published2, $draft]);

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $useCase = new GetDashboardSummaryUseCase($this->entities, $this->entityTypes, $this->accessLogs, new UtcClock());
        $registrar = new DashboardRouteRegistrar(
            new GetDashboardSummaryHandler($useCase, $jsonResponse),
        );

        $application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [],
            routeRegistrars: [$registrar],
        ))->create();

        $response = $application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/dashboard'),
        );

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $payload['entity_type_summary']);

        $summary = $payload['entity_type_summary'][0];
        self::assertSame(1, $summary['entity_type_id']);
        self::assertSame('Article', $summary['entity_type_name']);
        self::assertSame('article', $summary['entity_type_slug']);
        self::assertSame(2, $summary['published_count']);
        self::assertSame(1, $summary['draft_count']);
    }
}
