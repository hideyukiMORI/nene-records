<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Analytics;

use DateTimeImmutable;
use DateTimeZone;
use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Analytics\AccessLogEntry;
use NeNeRecords\Analytics\PdoAccessLogRepository;
use PHPUnit\Framework\TestCase;

final class PdoAccessLogRepositoryTest extends TestCase
{
    private PdoDatabaseQueryExecutor $executor;
    private PdoAccessLogRepository $repository;

    /** @var RequestScopedHolder<int> */
    private RequestScopedHolder $orgId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->executor = new PdoDatabaseQueryExecutor(new PdoConnectionFactory(new DatabaseConfig(
            null,
            'test',
            'sqlite',
            'localhost',
            1,
            ':memory:',
            'nene-records-test',
            '',
            'utf8',
        )));

        $this->orgId = new RequestScopedHolder();
        $this->orgId->set(0);

        foreach ($this->schemaStatements() as $statement) {
            $this->executor->execute($statement);
        }

        $this->repository = new PdoAccessLogRepository($this->executor, $this->orgId);
    }

    /**
     * @return list<string>
     */
    private function schemaStatements(): array
    {
        $path = dirname(__DIR__, 2) . '/database/schema/access_logs.sql';
        self::assertFileExists($path);
        $raw = trim((string) file_get_contents($path));
        $statements = [];

        foreach (preg_split('/;\R/s', $raw) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $statements[] = $statement;
            }
        }

        return $statements;
    }

    public function testInsertAndAggregateByDate(): void
    {
        $utc = new DateTimeZone('UTC');

        $this->repository->insert(new AccessLogEntry(
            requestId: 'req-1',
            method: 'GET',
            path: '/api/v1/tags',
            statusCode: 200,
            durationMs: 10.0,
            accessedAt: new DateTimeImmutable('2026-05-01T10:00:00+00:00', $utc),
        ));
        $this->repository->insert(new AccessLogEntry(
            requestId: 'req-2',
            method: 'POST',
            path: '/api/v1/tags',
            statusCode: 201,
            durationMs: 20.0,
            accessedAt: new DateTimeImmutable('2026-05-01T11:00:00+00:00', $utc),
        ));
        $this->repository->insert(new AccessLogEntry(
            requestId: 'req-3',
            method: 'GET',
            path: '/api/v1/entities',
            statusCode: 200,
            durationMs: 30.0,
            accessedAt: new DateTimeImmutable('2026-05-02T09:00:00+00:00', $utc),
        ));

        $items = $this->repository->aggregateByDate(
            new DateTimeImmutable('2026-05-01', $utc),
            new DateTimeImmutable('2026-05-02', $utc),
        );

        self::assertCount(2, $items);
        self::assertSame('2026-05-01', $items[0]->date);
        self::assertSame(2, $items[0]->requestCount);
        self::assertSame(15.0, $items[0]->avgDurationMs);
        self::assertSame('2026-05-02', $items[1]->date);
        self::assertSame(1, $items[1]->requestCount);
        self::assertSame(30.0, $items[1]->avgDurationMs);
    }

    public function testAggregateVisitorSummary(): void
    {
        $utc = new DateTimeZone('UTC');
        $at = static fn (string $iso): DateTimeImmutable => new DateTimeImmutable($iso, $utc);

        // Two distinct visitors (hashes h1, h2); h1 visits twice. One bot, three humans.
        $this->repository->insert($this->visitorEntry($at('2026-05-01T10:00:00+00:00'), 'h1', 'google.com', 'news', 'email', 'lawfirm1', false));
        $this->repository->insert($this->visitorEntry($at('2026-05-01T11:00:00+00:00'), 'h1', 'google.com', 'news', 'email', 'lawfirm1', false));
        $this->repository->insert($this->visitorEntry($at('2026-05-02T09:00:00+00:00'), 'h2', 't.co', null, null, 'lawfirm2', false));
        $this->repository->insert($this->visitorEntry($at('2026-05-02T09:30:00+00:00'), 'h3', null, null, null, null, true));

        $summary = $this->repository->aggregateVisitorSummary($at('2026-05-01'), $at('2026-05-02'), 10);

        self::assertSame(3, $summary->uniqueVisitors);
        self::assertSame(0.25, $summary->botRate);
        self::assertSame([['host' => 'google.com', 'count' => 2], ['host' => 't.co', 'count' => 1]], $summary->topReferrers);
        self::assertSame([['source' => 'news', 'medium' => 'email', 'campaign' => null, 'count' => 2]], $summary->utm);
        self::assertSame([['ref' => 'lawfirm1', 'count' => 2], ['ref' => 'lawfirm2', 'count' => 1]], $summary->ref);
    }

    public function testAggregateVisitorSummaryIsEmptyWhenNoVisitorData(): void
    {
        $utc = new DateTimeZone('UTC');
        $this->repository->insert(new AccessLogEntry(
            requestId: 'req-1',
            method: 'GET',
            path: '/api/v1/tags',
            statusCode: 200,
            durationMs: 10.0,
            accessedAt: new DateTimeImmutable('2026-05-01T10:00:00+00:00', $utc),
        ));

        $summary = $this->repository->aggregateVisitorSummary(
            new DateTimeImmutable('2026-05-01', $utc),
            new DateTimeImmutable('2026-05-02', $utc),
            10,
        );

        self::assertSame(0, $summary->uniqueVisitors);
        self::assertNull($summary->botRate);
        self::assertSame([], $summary->topReferrers);
        self::assertSame([], $summary->utm);
        self::assertSame([], $summary->ref);
    }

    private function visitorEntry(
        DateTimeImmutable $at,
        string $hash,
        ?string $refererHost,
        ?string $utmSource,
        ?string $utmMedium,
        ?string $ref,
        bool $isBot,
    ): AccessLogEntry {
        return new AccessLogEntry(
            requestId: null,
            method: 'GET',
            path: '/services',
            statusCode: 200,
            durationMs: 5.0,
            accessedAt: $at,
            visitorHash: $hash,
            refererHost: $refererHost,
            utmSource: $utmSource,
            utmMedium: $utmMedium,
            utmCampaign: null,
            ref: $ref,
            clientType: $isBot ? 'bot' : 'desktop',
            isBot: $isBot,
        );
    }

    public function testStatusDistribution(): void
    {
        $utc = new DateTimeZone('UTC');
        $at = new DateTimeImmutable('2026-05-01T10:00:00+00:00', $utc);

        foreach ([200, 200, 301, 404, 500] as $i => $status) {
            $this->repository->insert(new AccessLogEntry(
                requestId: null,
                method: 'GET',
                path: '/p' . $i,
                statusCode: $status,
                durationMs: 1.0,
                accessedAt: $at,
            ));
        }

        $dist = $this->repository->statusDistribution(
            new DateTimeImmutable('2026-05-01', $utc),
            new DateTimeImmutable('2026-05-01', $utc),
        );

        self::assertSame(['2xx' => 2, '3xx' => 1, '4xx' => 1, '5xx' => 1], $dist);
    }

    public function testPopularPagesCountsPublicPagesAndBeaconButExcludesApiMediaAndErrors(): void
    {
        $utc = new DateTimeZone('UTC');
        $at = new DateTimeImmutable('2026-05-01T10:00:00+00:00', $utc);
        $add = function (string $method, string $path, int $status) use ($at): void {
            $this->repository->insert(new AccessLogEntry(
                requestId: null,
                method: $method,
                path: $path,
                statusCode: $status,
                durationMs: 1.0,
                accessedAt: $at,
            ));
        };

        $add('GET', '/services', 200);
        $add('GET', '/services', 200);
        $add('BEACON', '/clear-lp', 204);      // LP beacon counts as a page view
        $add('GET', '/api/v1/entities/1', 200); // excluded (api)
        $add('GET', '/media/lg/x.jpg', 200);    // excluded (media)
        $add('GET', '/robots.txt', 200);        // excluded
        $add('GET', '/services', 404);          // excluded (error)

        $pages = $this->repository->popularPages(
            new DateTimeImmutable('2026-05-01', $utc),
            new DateTimeImmutable('2026-05-01', $utc),
            10,
        );

        self::assertSame([
            ['path' => '/services', 'count' => 2],
            ['path' => '/clear-lp', 'count' => 1],
        ], $pages);
    }
}
