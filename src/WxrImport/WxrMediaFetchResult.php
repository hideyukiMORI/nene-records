<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

/** Bytes + metadata for a media file fetched from its original WordPress URL. */
final readonly class WxrMediaFetchResult
{
    public function __construct(
        public string $bytes,
        public string $mimeType,
        public string $filename,
    ) {
    }
}
