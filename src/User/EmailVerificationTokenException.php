<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use DomainException;

/**
 * Raised when an email-change verification token cannot be honored.
 *
 * `expired` distinguishes a known-but-stale token (→ 410 Gone) from an unknown/invalid
 * one (→ 422 Unprocessable Entity).
 */
final class EmailVerificationTokenException extends DomainException
{
    private function __construct(
        public readonly bool $expired,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function invalid(): self
    {
        return new self(false, 'Email verification token is invalid.');
    }

    public static function expired(): self
    {
        return new self(true, 'Email verification token has expired.');
    }
}
