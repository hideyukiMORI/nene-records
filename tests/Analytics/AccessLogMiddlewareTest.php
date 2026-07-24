<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Analytics;

use DateTimeImmutable;
use Nene2\Http\RequestScopedHolder;
use Nene2\Http\UtcClock;
use NeNeRecords\Analytics\AccessLogMiddleware;
use NeNeRecords\Analytics\AccessLogRepositoryInterface;
use NeNeRecords\Analytics\AnalyticsSaltRepositoryInterface;
use NeNeRecords\Analytics\VisitorHasher;
use NeNeRecords\Setting\SettingRepositoryInterface;
use NeNeRecords\Setting\SettingValue;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;

final class AccessLogMiddlewareTest extends TestCase
{
    private const SALT = "\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01\x01";

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
        $response = $this->makeMiddleware()->process(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/tags')
                ->withAttribute('nene2.request_id', 'req-abc'),
            $this->handler(200),
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
        $this->makeMiddleware()->process(
            $this->factory->createServerRequest('GET', 'https://example.test/health'),
            $this->handler(200),
        );

        self::assertSame([], $this->repository->all());
    }

    public function testRecordsHomepageNowThatSlashIsNoLongerExcluded(): void
    {
        $this->makeMiddleware()->process(
            $this->factory->createServerRequest('GET', 'https://example.test/'),
            $this->handler(200),
        );

        self::assertCount(1, $this->repository->all());
        self::assertSame('/', $this->repository->all()[0]->path);
    }

    public function testOptInOffLeavesVisitorFieldsNull(): void
    {
        $this->makeMiddleware(optInValue: null)->process(
            $this->factory->createServerRequest('GET', 'https://example.test/services?utm_source=news&ref=lawfirm1')
                ->withHeader('Referer', 'https://google.com/search?q=x')
                ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'),
            $this->handler(200),
        );

        $entry = $this->repository->all()[0];
        self::assertNull($entry->visitorHash);
        self::assertNull($entry->refererHost);
        self::assertNull($entry->utmSource);
        self::assertNull($entry->ref);
        self::assertNull($entry->clientType);
        self::assertNull($entry->isBot);
    }

    public function testOptInOnPopulatesPrivacyFirstVisitorFields(): void
    {
        $this->makeMiddleware(optInValue: 'true')->process(
            $this->factory->createServerRequest('GET', 'https://example.test/services?utm_source=news&utm_medium=email&ref=lawfirm1&secret=leak')
                ->withHeader('Referer', 'https://www.google.com/search?q=x')
                ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'),
            $this->handler(200),
        );

        $entry = $this->repository->all()[0];
        // Raw IP is never stored — only the daily-salted org-scoped hash.
        self::assertSame(VisitorHasher::hash(self::SALT, 'unknown', 1), $entry->visitorHash);
        self::assertSame('www.google.com', $entry->refererHost);
        self::assertSame('news', $entry->utmSource);
        self::assertSame('email', $entry->utmMedium);
        self::assertNull($entry->utmCampaign);
        self::assertSame('lawfirm1', $entry->ref);
        self::assertSame('mobile', $entry->clientType);
        self::assertFalse($entry->isBot);
    }

    public function testDoesNotFailRequestWhenInsertFails(): void
    {
        $throwing = new readonly class () implements AccessLogRepositoryInterface {
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

            public function aggregateEntityViews(string $sinceDate): array
            {
                return [];
            }
        };

        $response = $this->makeMiddleware(repository: $throwing)->process(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/tags'),
            $this->handler(200),
        );

        self::assertSame(200, $response->getStatusCode());
    }

    private function makeMiddleware(
        ?AccessLogRepositoryInterface $repository = null,
        ?string $optInValue = null,
    ): AccessLogMiddleware {
        $settings = $this->createStub(SettingRepositoryInterface::class);
        $settings->method('findValueByKey')->willReturn(
            $optInValue === null
                ? null
                : new SettingValue('analytics_visitor_tracking', $optInValue, false, null, null, null, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
        );

        $salts = $this->createStub(AnalyticsSaltRepositoryInterface::class);
        $salts->method('saltForDate')->willReturn(self::SALT);

        /** @var RequestScopedHolder<int> $orgId */
        $orgId = new RequestScopedHolder();
        $orgId->set(1);

        return new AccessLogMiddleware(
            $repository ?? $this->repository,
            new NullLogger(),
            new UtcClock(),
            $settings,
            $salts,
            $orgId,
        );
    }

    private function handler(int $status): RequestHandlerInterface
    {
        return new readonly class ($this->factory, $status) implements RequestHandlerInterface {
            public function __construct(private Psr17Factory $factory, private int $status)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->factory->createResponse($this->status);
            }
        };
    }
}
