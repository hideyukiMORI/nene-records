<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

/** An item that would be skipped (unsupported post_type / status). */
final readonly class WxrImportSkippedItem
{
    public function __construct(
        public string $title,
        public string $reason,
    ) {
    }
}
