<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Analytics;

use DateTimeImmutable;
use NeNeRecords\Analytics\AccessLogMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;

final class AccessLogMiddlewareTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryAccessLogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->repository = new InMemoryAccessLogRepository();
    }

    public function testPersistsAccessLogForApiRequest(): void
    {
        $middleware = new AccessLogMiddleware($this->repository, new NullLogger(), excludedPaths: ['/health']);

        $response = $middleware->process(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/tags')
                ->withAttribute('nene2.request_id', 'req-abc'),
            new readonly class ($this->factory) implements RequestHandlerInterface {
                public function __construct(private Psr17Factory $factory)
                {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->factory->createResponse(200);
                }
            },
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $this->repository->all());

        $entry = $this->repository->all()[0];
        self::assertSame('req-abc', $entry->requestId);
        self::assertSame('GET', $entry->method);
        self::assertSame('/api/v1/tags', $entry->path);
        self::assertSame(200, $entry->statusCode);
        self::assertGreaterThanOrEqual(0.0, $entry->durationMs);
    }

    public function testSkipsExcludedPaths(): void
    {
        $middleware = new AccessLogMiddleware($this->repository, new NullLogger());

        $middleware->process(
            $this->factory->createServerRequest('GET', 'https://example.test/health'),
            new readonly class ($this->factory) implements RequestHandlerInterface {
                public function __construct(private Psr17Factory $factory)
                {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->factory->createResponse(200);
                }
            },
        );

        self::assertSame([], $this->repository->all());
    }

    public function testDoesNotFailRequestWhenInsertFails(): void
    {
        $middleware = new AccessLogMiddleware(
            new readonly class () implements \NeNeRecords\Analytics\AccessLogRepositoryInterface {
                public function insert(\NeNeRecords\Analytics\AccessLogEntry $entry): void
                {
                    throw new \RuntimeException('db down');
                }

                public function countByDate(DateTimeImmutable $date): int
                {
                    return 0;
                }

                public function countByYearMonth(int $year, int $month): int
                {
                    return 0;
                }

                public function aggregateByDate(DateTimeImmutable $from, DateTimeImmutable $to): array
                {
                    return [];
                }
            },
            new NullLogger(),
            excludedPaths: ['/health'],
        );

        $response = $middleware->process(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/tags'),
            new readonly class ($this->factory) implements RequestHandlerInterface {
                public function __construct(private Psr17Factory $factory)
                {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->factory->createResponse(200);
                }
            },
        );

        self::assertSame(200, $response->getStatusCode());
    }
}
