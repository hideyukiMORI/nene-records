<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

/**
 * Extracts the attribution allowlist from a query string (ADR 0006 D4).
 *
 * The raw query is never stored — only `utm_source / utm_medium / utm_campaign` and `ref`
 * (outreach campaign identifier) are pulled out; everything else (which may carry PII such
 * as emails, tokens or search terms) is discarded. Values are trimmed and length-capped.
 */
final class QueryAttribution
{
    private const MAX_LENGTH = 255;

    /**
     * @return array{utmSource: ?string, utmMedium: ?string, utmCampaign: ?string, ref: ?string}
     */
    public static function fromQueryString(string $query): array
    {
        parse_str(ltrim($query, '?'), $params);

        return [
            'utmSource' => self::pick($params, 'utm_source'),
            'utmMedium' => self::pick($params, 'utm_medium'),
            'utmCampaign' => self::pick($params, 'utm_campaign'),
            'ref' => self::pick($params, 'ref'),
        ];
    }

    /**
     * @param array<array-key, mixed> $params
     */
    private static function pick(array $params, string $key): ?string
    {
        $value = $params[$key] ?? null;

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, self::MAX_LENGTH);
    }
}
