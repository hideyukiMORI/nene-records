<?php

declare(strict_types=1);

namespace NeNeRecords\UrlRedirect;

/**
 * Result of a CSV 301-redirect import (#651 PR4). In preview mode `importedRows`
 * is 0 — nothing is written. `samples` holds the first normalized mappings so the
 * operator can sanity-check before committing; `errors` lists per-row problems.
 */
final readonly class ImportRedirectsCsvOutput
{
    /**
     * @param list<array{line: int, message: string}> $errors
     * @param list<array{source: string, target: string}> $samples
     */
    public function __construct(
        public bool $dryRun,
        public int $totalRows,
        public int $validRows,
        public int $importedRows,
        public int $skippedRows,
        public array $errors,
        public array $samples,
    ) {
    }
}
