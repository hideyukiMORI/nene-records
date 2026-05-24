<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Analytics;

use DateTimeImmutable;
use DateTimeZone;
use Nene2\Validation\ValidationException;
use NeNeRecords\Analytics\AccessStatsDateRangeParser;
use NeNeRecords\Analytics\GetAccessStatsByDateInput;
use NeNeRecords\Analytics\GetAccessStatsByDateUseCase;
use PHPUnit\Framework\TestCase;

final class AccessStatsDateRangeParserTest extends TestCase
{
    public function testParseValidRange(): void
    {
        $range = AccessStatsDateRangeParser::parse([
            'from' => '2026-05-01',
            'to' => '2026-05-03',
        ]);

        self::assertSame('2026-05-01', $range->from->format('Y-m-d'));
        self::assertSame('2026-05-03', $range->to->format('Y-m-d'));
    }

    public function testFromAfterToThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        AccessStatsDateRangeParser::parse([
            'from' => '2026-05-10',
            'to' => '2026-05-01',
        ]);
    }

    public function testUseCaseReturnsEmptyItemsWhenNoLogs(): void
    {
        $repository = new InMemoryAccessLogRepository();
        $useCase = new GetAccessStatsByDateUseCase($repository);
        $utc = new DateTimeZone('UTC');

        $output = $useCase->execute(new GetAccessStatsByDateInput(
            from: new DateTimeImmutable('2026-05-01', $utc),
            to: new DateTimeImmutable('2026-05-02', $utc),
        ));

        self::assertSame([], $output->items);
    }
}
