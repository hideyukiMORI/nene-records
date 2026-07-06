<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

/**
 * Result of an {@see SsrfGuard} inspection of an outbound URL.
 *
 * On success it carries the resolved, verified-public IP addresses so the caller
 * can pin the connection to them (e.g. cURL's {@see CURLOPT_RESOLVE}) and avoid a
 * DNS-rebinding TOCTOU between the check and the actual connect.
 */
final readonly class SsrfInspection
{
    /** @param list<string> $addresses */
    private function __construct(
        public bool $allowed,
        public ?string $reason,
        public array $addresses,
    ) {
    }

    /** @param list<string> $addresses resolved, verified-public IP addresses */
    public static function allow(array $addresses): self
    {
        return new self(true, null, $addresses);
    }

    public static function reject(string $reason): self
    {
        return new self(false, $reason, []);
    }
}
