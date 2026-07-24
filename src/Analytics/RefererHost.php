<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

/**
 * Extracts the host from a Referer (ADR 0006 D3).
 *
 * Only the host is kept — never the full referer URL, whose path/query can carry PII.
 * Returns null for empty, relative, or unparseable referers.
 */
final class RefererHost
{
    private const MAX_LENGTH = 255;

    public static function fromReferer(string $referer): ?string
    {
        $referer = trim($referer);

        if ($referer === '') {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            return null;
        }

        return mb_substr(strtolower($host), 0, self::MAX_LENGTH);
    }
}
