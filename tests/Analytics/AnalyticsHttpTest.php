<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Analytics;

use DateTimeImmutable;
use DateTimeZone;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\Http\UtcClock;
use NeNeRecords\Analytics\AccessLogEntry;
use NeNeRecords\Analytics\AnalyticsRouteRegistrar;
use NeNeRecords\Analytics\GetAccessStatsByDateHandler;
use NeNeRecords\Analytics\GetAccessStatsByDateUseCase;
use NeNeRecords\Analytics\GetPopularEntitiesHandler;
use NeNeRecords\Analytics\GetPopularEntitiesUseCase;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AnalyticsHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryAccessLogRepository $repository;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->repository = new InMemoryAccessLogRepository();

        $utc = new DateTimeZone('UTC');
        $this->repository->insert(new AccessLogEntry(
            requestId: null,
            method: 'GET',
            path: '/api/v1/tags',
            statusCode: 200,
            durationMs: 10.0,
            accessedAt: new DateTimeImmutable('2026-05-01T12:00:00+00:00', $utc),
        ));
        $this->repository->insert(new AccessLogEntry(
            requestId: null,
            method: 'GET',
            path: '/api/v1/entities',
            statusCode: 200,
            durationMs: 30.0,
            accessedAt: new DateTimeImmutable('2026-05-02T08:00:00+00:00', $utc),
        ));

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $entities = new InMemoryEntityRepository([
            new Entity(id: 7, entityTypeId: 3, slug: 'popular', status: EntityStatus::Published),
        ]);
        $registrar = new AnalyticsRouteRegistrar(
            new GetAccessStatsByDateHandler(
                new GetAccessStatsByDateUseCase($this->repository),
                $jsonResponse,
            ),
            new GetPopularEntitiesHandler(
                new GetPopularEntitiesUseCase(
                    $this->repository,
                    $entities,
                    new InMemoryTextFieldRepository(),
                    new UtcClock(),
                ),
                $jsonResponse,
            ),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testGetAccessStatsByDateReturnsAggregatedItems(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest(
                'GET',
                'https://example.test/api/v1/analytics/access-stats?from=2026-05-01&to=2026-05-02',
            ),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('2026-05-01', $payload['from']);
        self::assertSame('2026-05-02', $payload['to']);
        self::assertCount(2, $payload['items']);
        self::assertSame('2026-05-01', $payload['items'][0]['date']);
        self::assertSame(1, $payload['items'][0]['request_count']);
        self::assertSame(10.0, $payload['items'][0]['avg_duration_ms']);
        self::assertArrayHasKey('visitor', $payload);
        self::assertNull($payload['visitor']); // no opt-in visitor data seeded → null
    }

    public function testGetPopularEntitiesReturnsPublishedRankedByViews(): void
    {
        $now = new DateTimeImmutable();
        for ($i = 0; $i < 4; ++$i) {
            $this->repository->insert(new AccessLogEntry(
                requestId: null,
                method: 'GET',
                path: '/api/v1/entities/7',
                statusCode: 200,
                durationMs: 5.0,
                accessedAt: $now,
            ));
        }

        $response = $this->application->handle(
            $this->factory->createServerRequest(
                'GET',
                'https://example.test/api/v1/analytics/popular-entities?days=30&limit=5',
            ),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $payload['items']);
        self::assertSame(7, $payload['items'][0]['entity_id']);
        self::assertSame(3, $payload['items'][0]['entity_type_id']);
        self::assertSame(4, $payload['items'][0]['view_count']);
    }

    public function testMissingFromReturns422(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest(
                'GET',
                'https://example.test/api/v1/analytics/access-stats?to=2026-05-02',
            ),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(422, $response->getStatusCode());
        self::assertStringEndsWith('validation-failed', (string) $payload['type']);
    }

    public function testInvalidDateFormatReturns422(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest(
                'GET',
                'https://example.test/api/v1/analytics/access-stats?from=2026/05/01&to=2026-05-02',
            ),
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
