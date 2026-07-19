<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * Builds the schema.org `Organization` node for public pages (#978) from the org's
 * existing public settings — the single signal Google's company knowledge panel reads.
 *
 * Reuses settings the admin already fills elsewhere (no new knobs):
 *  - name         ← `site_name`
 *  - url          ← the site home (passed in, base-path aware)
 *  - logo         ← `logo_media_id` (absolutized by the caller; omitted when unset)
 *  - sameAs       ← `footer_config.social[].url` (the footer's canonical SNS links)
 *  - contactPoint ← `header_config.topbar.email` / `.phone` (omitted when both blank)
 *
 * Unset fields are dropped so a site that configured nothing keeps a bare
 * `{@type, name, url}` — identical in spirit to the previous name-only publisher.
 * Follows the {@see \NeNeRecords\Http\WebAnalyticsConfig}::fromSettings() convention.
 */
final class PublicOrganizationSchema
{
    /**
     * @param array<string, string> $settings resolved public settings map
     * @return array<string, mixed> a schema.org Organization node (no @context)
     */
    public static function build(string $siteName, string $homeUrl, ?string $logoUrl, array $settings): array
    {
        $org = [
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => $homeUrl,
        ];

        if ($logoUrl !== null && $logoUrl !== '') {
            $org['logo'] = $logoUrl;
        }

        $sameAs = self::socialUrls($settings['footer_config'] ?? '');
        if ($sameAs !== []) {
            $org['sameAs'] = $sameAs;
        }

        $contactPoint = self::contactPoint($settings['header_config'] ?? '');
        if ($contactPoint !== null) {
            $org['contactPoint'] = $contactPoint;
        }

        return $org;
    }

    /**
     * Distinct, non-empty SNS profile URLs from `footer_config.social`.
     *
     * @return list<string>
     */
    private static function socialUrls(string $footerConfigJson): array
    {
        if ($footerConfigJson === '') {
            return [];
        }

        /** @var mixed $decoded */
        $decoded = json_decode($footerConfigJson, true);
        if (!is_array($decoded) || !isset($decoded['social']) || !is_array($decoded['social'])) {
            return [];
        }

        $urls = [];
        foreach ($decoded['social'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $url = $item['url'] ?? null;
            if (is_string($url) && $url !== '' && !in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * A schema.org ContactPoint from `header_config.topbar` email/phone, or null when
     * neither is set (schema.org requires at least one contact method).
     *
     * @return array<string, string>|null
     */
    private static function contactPoint(string $headerConfigJson): ?array
    {
        if ($headerConfigJson === '') {
            return null;
        }

        /** @var mixed $decoded */
        $decoded = json_decode($headerConfigJson, true);
        if (!is_array($decoded) || !isset($decoded['topbar']) || !is_array($decoded['topbar'])) {
            return null;
        }
        $topbar = $decoded['topbar'];

        $email = is_string($topbar['email'] ?? null) ? trim($topbar['email']) : '';
        $phone = is_string($topbar['phone'] ?? null) ? trim($topbar['phone']) : '';
        if ($email === '' && $phone === '') {
            return null;
        }

        $contactPoint = ['@type' => 'ContactPoint', 'contactType' => 'sales'];
        if ($email !== '') {
            $contactPoint['email'] = $email;
        }
        if ($phone !== '') {
            $contactPoint['telephone'] = $phone;
        }

        return $contactPoint;
    }
}
