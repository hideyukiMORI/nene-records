<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

/**
 * A category/tag definition from the WXR channel header
 * (`<wp:category>` / `<wp:tag>`), used to resolve slug → display name.
 */
final readonly class WxrTerm
{
    public function __construct(
        public string $kind, // 'category' | 'tag'
        public string $slug,
        public string $name,
    ) {
    }
}
