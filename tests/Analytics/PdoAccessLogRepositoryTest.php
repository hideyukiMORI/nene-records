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
}
