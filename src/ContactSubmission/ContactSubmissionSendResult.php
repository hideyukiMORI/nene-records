<?php

declare(strict_types=1);

namespace NeNeRecords\ContactSubmission;

/**
 * The outcome of handing one submission to the issuing product.
 *
 * Carries a reason for the operator log, never for the visitor: an abuser must not learn
 * which part of the pipeline rejected them.
 */
final readonly class ContactSubmissionSendResult
{
    private function __construct(
        public bool $delivered,
        public ?string $reason = null,
        public ?int $upstreamStatus = null,
    ) {
    }

    public static function delivered(): self
    {
        return new self(true);
    }

    public static function failed(string $reason, ?int $upstreamStatus = null): self
    {
        return new self(false, $reason, $upstreamStatus);
    }
}
