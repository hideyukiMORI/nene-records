<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use DateTimeImmutable;

final readonly class AccessLogEntry
{
    public function __construct(
        public ?string $requestId,
        public string $method,
        public string $path,
        public int $statusCode,
        public float $durationMs,
        public DateTimeImmutable $accessedAt,
    ) {
    }
}
