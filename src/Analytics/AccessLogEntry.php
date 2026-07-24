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
        // Path B visitor fields (ADR 0006). All nullable — populated only when the owning
        // org opts in (`analytics_visitor_tracking`); null preserves the pre-Path-B row.
        public ?string $visitorHash = null,
        public ?string $refererHost = null,
        public ?string $utmSource = null,
        public ?string $utmMedium = null,
        public ?string $utmCampaign = null,
        public ?string $ref = null,
        public ?string $clientType = null,
        public ?bool $isBot = null,
    ) {
    }
}
