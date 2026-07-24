<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Analytics;

use NeNeRecords\Analytics\QueryAttribution;
use PHPUnit\Framework\TestCase;

final class QueryAttributionTest extends TestCase
{
    public function testExtractsAllowlistedParams(): void
    {
        $result = QueryAttribution::fromQueryString('?utm_source=news&utm_medium=email&utm_campaign=spring&ref=lawfirm1');

        self::assertSame('news', $result['utmSource']);
        self::assertSame('email', $result['utmMedium']);
        self::assertSame('spring', $result['utmCampaign']);
        self::assertSame('lawfirm1', $result['ref']);
    }

    public function testDropsEverythingOutsideTheAllowlist(): void
    {
        $result = QueryAttribution::fromQueryString('q=alice@example.com&token=secret&utm_source=news');

        self::assertSame('news', $result['utmSource']);
        self::assertNull($result['utmMedium']);
        self::assertNull($result['ref']);
    }

    public function testEmptyOrMissingYieldsNulls(): void
    {
        $result = QueryAttribution::fromQueryString('');

        self::assertNull($result['utmSource']);
        self::assertNull($result['utmMedium']);
        self::assertNull($result['utmCampaign']);
        self::assertNull($result['ref']);
    }

    public function testCapsLength(): void
    {
        $result = QueryAttribution::fromQueryString('ref=' . str_repeat('x', 500));

        self::assertNotNull($result['ref']);
        self::assertSame(255, mb_strlen($result['ref']));
    }
}
