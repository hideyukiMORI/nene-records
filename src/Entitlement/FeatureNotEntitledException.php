<?php

declare(strict_types=1);

namespace NeNeRecords\Entitlement;

use RuntimeException;

/**
 * Raised when an organization attempts to use a feature its plan does not include
 * (e.g. assigning a custom domain on a plan without `customDomainAllowed`).
 * Mapped to 402 Payment Required by {@see FeatureNotEntitledExceptionHandler}.
 */
final class FeatureNotEntitledException extends RuntimeException
{
    public function __construct(string $feature)
    {
        parent::__construct("This feature is not available on the current plan: {$feature}.");
    }
}
