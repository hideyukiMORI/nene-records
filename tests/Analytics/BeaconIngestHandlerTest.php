<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Analytics;

use Nene2\Http\RequestScopedHolder;
use Nene2\Http\UtcClock;
use Nene2\Middleware\RateLimitStorageInterface;
use NeNeRecords\Analytics\AnalyticsSaltRepositoryInterface;
use NeNeRecords\Analytics\BeaconIngestHandler;
use NeNeRecords\Analytics\VisitorFieldsResolver;
use NeNeRecords\Setting\SettingRepositoryInterface;
use NeNeRecords\Setting\SettingValue;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class BeaconIngestHandlerTest extends TestCase
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

    public function testAllowedPathRecordsBeaconRowAndReturns204(): void
    {
        $response = $this->handle('{"path":"/clear-lp","referrer":"https://google.com/x","query":"?ref=lp1"}');

        self::assertSame(204, $response->getStatusCode());
        self::assertCount(1, $this->repository->all());

        $entry = $this->repository->all()[0];
        self::assertSame('BEACON', $entry->method);
        self::assertSame('/clear-lp', $entry->path);
        self::assertSame(204, $entry->statusCode);
        // Opt-in OFF (default): visitor fields stay null.
        self::assertNull($entry->visitorHash);
        self::assertNull($entry->ref);
    }

    public function testOptInOnPopulatesVisitorFields(): void
    {
        $response = $this->handle(
            '{"path":"/clear-lp","referrer":"https://www.google.com/x","query":"?utm_source=news&ref=lp1&secret=leak"}',
            optInValue: 'true',
        );

        self::assertSame(204, $response->getStatusCode());
        $entry = $this->repository->all()[0];
        self::assertNotNull($entry->visitorHash);
        self::assertSame(64, strlen((string) $entry->visitorHash));
        self::assertSame('www.google.com', $entry->refererHost);
        self::assertSame('news', $entry->utmSource);
        self::assertSame('lp1', $entry->ref);
    }

    public function testTrailingSlashIsNormalised(): void
    {
        $this->handle('{"path":"/clear-lp/"}');

        self::assertCount(1, $this->repository->all());
        self::assertSame('/clear-lp', $this->repository->all()[0]->path);
    }

    public function testUnknownPathIsDroppedButStillReturns204(): void
    {
        $response = $this->handle('{"path":"/admin/secret"}');

        self::assertSame(204, $response->getStatusCode());
        self::assertSame([], $this->repository->all());
    }

    public function testMalformedBodyIsDropped(): void
    {
        $response = $this->handle('not-json');

        self::assertSame(204, $response->getStatusCode());
        self::assertSame([], $this->repository->all());
    }

    public function testRateLimitExceededReturns429AndRecordsNothing(): void
    {
        $response = $this->handle('{"path":"/clear-lp"}', hitCount: 999);

        self::assertSame(429, $response->getStatusCode());
        self::assertSame([], $this->repository->all());
    }

    private function handle(string $body, ?string $optInValue = null, int $hitCount = 1): \Psr\Http\Message\ResponseInterface
    {
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

        $rateLimit = $this->createStub(RateLimitStorageInterface::class);
        $rateLimit->method('hit')->willReturn(['count' => $hitCount, 'reset_at' => 0]);

        $handler = new BeaconIngestHandler(
            $this->repository,
            new VisitorFieldsResolver($settings, $salts, new UtcClock(), $orgId),
            new UtcClock(),
            $rateLimit,
            $this->factory,
        );

        $request = $this->factory->createServerRequest('POST', 'https://ayane.co.jp/api/v1/public/beacon')
            ->withBody($this->factory->createStream($body));

        return $handler->handle($request);
    }
}
