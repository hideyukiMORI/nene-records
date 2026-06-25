<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

/**
 * Resolved web-analytics configuration for the public surface (GA4 / GTM +
 * Consent Mode v2 default). Sourced from the org's public settings
 * (`analytics_gtm_id` / `analytics_ga4_id` / `analytics_consent_default`) and
 * consumed by {@see PublicHtmlCsp::build()} (CSP allowlist) and
 * {@see WebAnalyticsHeadSnippet::render()} (the `<head>` tag).
 *
 * IDs are validated to a strict `[A-Za-z0-9_-]` shape on construction: a stored
 * value that does not match is treated as "not set" (null). This both gates the
 * feature on a real ID and removes any injection surface, since the validated
 * value is later interpolated verbatim into the CSP header and an inline script.
 */
final readonly class WebAnalyticsConfig
{
    /** GTM `GTM-XXXXXXX` / GA4 `G-XXXXXXXXXX` shape — alphanumerics, `_` and `-`. */
    private const ID_PATTERN = '/^[A-Za-z0-9_-]{4,40}$/';

    public function __construct(
        public ?string $gtmId,
        public ?string $ga4Id,
        /** Consent Mode v2 default: `denied` (EU-safe) or `granted`. */
        public string $consentDefault,
    ) {
    }

    public static function disabled(): self
    {
        return new self(null, null, 'denied');
    }

    /**
     * Build from a flat `settingKey => effectiveValue` map (public settings).
     *
     * @param array<string, string> $settings
     */
    public static function fromSettings(array $settings): self
    {
        return new self(
            self::normalizeId($settings['analytics_gtm_id'] ?? ''),
            self::normalizeId($settings['analytics_ga4_id'] ?? ''),
            ($settings['analytics_consent_default'] ?? 'denied') === 'granted' ? 'granted' : 'denied',
        );
    }

    /** Analytics is active only when at least one valid tag id is configured. */
    public function isEnabled(): bool
    {
        return $this->gtmId !== null || $this->ga4Id !== null;
    }

    private static function normalizeId(string $raw): ?string
    {
        $trimmed = trim($raw);

        return preg_match(self::ID_PATTERN, $trimmed) === 1 ? $trimmed : null;
    }
}
