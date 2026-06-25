<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * Builds the `robots.txt` body: keeps crawlers out of the back office while
 * leaving public content crawlable, and advertises the XML sitemap.
 *
 * `/api` is intentionally NOT disallowed — Googlebot fetches it when rendering
 * the SPA-shell listing pages (home / browse / search), so blocking it would
 * stop those pages from rendering.
 */
final class RobotsTxtRenderer
{
    /** Back-office surfaces that should never be crawled or indexed. */
    private const DISALLOW = ['/admin', '/superadmin', '/login'];

    private function __construct()
    {
    }

    public static function render(string $sitemapUrl): string
    {
        $lines = ['User-agent: *'];

        foreach (self::DISALLOW as $path) {
            $lines[] = 'Disallow: ' . $path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: ' . $sitemapUrl;

        return implode("\n", $lines) . "\n";
    }
}
