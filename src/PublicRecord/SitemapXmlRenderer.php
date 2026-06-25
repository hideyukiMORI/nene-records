<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * Renders a sitemaps.org 0.9 `<urlset>` document from resolved {@see SitemapUrl}s.
 * Paths are joined to the request base URL and XML-escaped.
 */
final class SitemapXmlRenderer
{
    private function __construct()
    {
    }

    /** @param list<SitemapUrl> $urls */
    public static function render(string $baseUrl, array $urls): string
    {
        $base = rtrim($baseUrl, '/');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>' . self::escape($base . $url->path) . '</loc>';

            if ($url->lastmod !== null) {
                $xml .= '<lastmod>' . self::escape($url->lastmod) . '</lastmod>';
            }

            $xml .= '</url>' . "\n";
        }

        return $xml . '</urlset>' . "\n";
    }

    /**
     * Render a `<sitemapindex>` pointing at child sitemaps. Each path is joined to
     * the base URL (e.g. `/sitemap.xml?page=2`).
     *
     * @param list<string> $childPaths
     */
    public static function renderIndex(string $baseUrl, array $childPaths): string
    {
        $base = rtrim($baseUrl, '/');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($childPaths as $path) {
            $xml .= '  <sitemap><loc>' . self::escape($base . $path) . '</loc></sitemap>' . "\n";
        }

        return $xml . '</sitemapindex>' . "\n";
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
