<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

/** Outcome of importing WXR attachments into the media library. */
final readonly class WxrMediaImportResult
{
    /** @param array<string, string> $urlMap original attachment URL → new media URL */
    public function __construct(
        public int $imported,
        public int $skipped,
        public array $urlMap,
    ) {
    }
}
