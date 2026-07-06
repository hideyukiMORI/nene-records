<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Support;

use DateTimeImmutable;
use Nene2\Http\ClockInterface;

/**
 * Test {@see ClockInterface} that always returns a fixed instant, so time-boundary
 * behaviour (token TTL/exp, scheduled publish, rate-limit windows) is deterministic.
 */
final readonly class FixedClock implements ClockInterface
{
    public function __construct(private string $instant = '2026-06-01T10:00:00+00:00')
    {
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->instant);
    }
}
