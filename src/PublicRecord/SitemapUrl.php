<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * One `<url>` entry for the XML sitemap: a site-relative path (joined with the
 * request's scheme+host at render time) and an optional W3C-datetime lastmod.
 */
final readonly class SitemapUrl
{
    public function __construct(
        public string $path,
        public ?string $lastmod = null,
    ) {
    }
}
