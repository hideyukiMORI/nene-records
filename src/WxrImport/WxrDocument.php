<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

/**
 * A parsed WordPress WXR export: channel metadata, content items, and the
 * category/tag definitions declared in the channel header.
 */
final readonly class WxrDocument
{
    /**
     * @param list<WxrItem> $items
     * @param list<WxrTerm> $terms
     */
    public function __construct(
        public string $siteTitle,
        public string $baseUrl,
        public array $items,
        public array $terms,
    ) {
    }
}
