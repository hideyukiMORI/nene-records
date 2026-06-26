<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * Derived chapter navigation for a record that belongs to a multi-chapter work
 * (小説家になろう/AO3 hub-and-spoke). Computed at render time from the record's
 * `series` / `chapter_no` / `chapter_total` fields — never baked into the body.
 */
final readonly class PublicRecordChapterNav
{
    public function __construct(
        /** URL of the work's 目次 (index) record — the canonical "front door". */
        public string $indexUrl,
        /** URL of the previous chapter, or null on the first chapter. */
        public ?string $prevUrl,
        /** URL of the next chapter, or null on the last chapter. */
        public ?string $nextUrl,
        public int $chapterNo,
        public int $chapterTotal,
    ) {
    }
}
