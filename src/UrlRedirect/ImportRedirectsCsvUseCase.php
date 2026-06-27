<?php

declare(strict_types=1);

namespace NeNeRecords\UrlRedirect;

/**
 * Bulk-imports 301 redirects from a CSV of `source,target` path pairs (#651 PR4),
 * so a migrated site keeps its old WordPress URLs 1:1. Paths are normalized
 * (full old URLs are accepted — only the path is kept), validated, de-duplicated
 * within the file, and upserted via the existing redirect repository. A dry run
 * validates and previews without writing.
 */
final readonly class ImportRedirectsCsvUseCase
{
    private const MAX_ROWS = 10000;
    private const SAMPLE_LIMIT = 20;
    private const ERROR_LIMIT = 100;

    /** First-cell keywords that mark a header row to skip. */
    private const HEADER_KEYS = ['source', 'source_path', 'from', 'old', 'old_url', 'old_path', 'url'];

    public function __construct(
        private UrlRedirectRepositoryInterface $redirects,
    ) {
    }

    public function execute(string $csv, bool $dryRun): ImportRedirectsCsvOutput
    {
        $errors = [];
        $samples = [];
        $seen = [];
        $valid = 0;
        $imported = 0;
        $skipped = 0;
        $dataRows = 0;
        $rowNumber = 0;

        foreach ($this->rows($csv) as $cols) {
            $rowNumber++;

            if ($rowNumber === 1 && $this->isHeader($cols)) {
                continue;
            }

            $dataRows++;
            if ($dataRows > self::MAX_ROWS) {
                $errors[] = [
                    'line' => $rowNumber,
                    'message' => 'Too many rows; processed the first ' . self::MAX_ROWS . '.',
                ];
                break;
            }

            $source = isset($cols[0]) ? $this->normalizePath($cols[0]) : null;
            $target = isset($cols[1]) ? $this->normalizePath($cols[1]) : null;

            $error = $this->validate($source, $target, $seen);
            if ($error !== null || $source === null || $target === null) {
                if (count($errors) < self::ERROR_LIMIT) {
                    $errors[] = ['line' => $rowNumber, 'message' => $error ?? 'Invalid row.'];
                }
                $skipped++;
                continue;
            }

            $seen[$source] = true;
            $valid++;

            if (count($samples) < self::SAMPLE_LIMIT) {
                $samples[] = ['source' => $source, 'target' => $target];
            }

            if (!$dryRun) {
                $this->redirects->save($source, $target);
                $imported++;
            }
        }

        return new ImportRedirectsCsvOutput(
            dryRun: $dryRun,
            totalRows: $valid + $skipped,
            validRows: $valid,
            importedRows: $imported,
            skippedRows: $skipped,
            errors: $errors,
            samples: $samples,
        );
    }

    /** @param array<string, bool> $seen */
    private function validate(?string $source, ?string $target, array $seen): ?string
    {
        if ($source === null) {
            return 'Invalid or empty source path.';
        }
        if ($target === null) {
            return 'Invalid or empty target path.';
        }
        if ($source === $target) {
            return 'Source and target are identical.';
        }
        if (isset($seen[$source])) {
            return 'Duplicate source path; the first occurrence wins.';
        }

        return null;
    }

    /** Normalize a raw cell to a leading-slash, no-trailing-slash path, or null if unusable. */
    private function normalizePath(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Accept full old URLs (https://old.example/page) — keep just the path.
        if (str_contains($raw, '://')) {
            $path = parse_url($raw, PHP_URL_PATH);
            $raw = is_string($path) ? $path : '';
        }

        if ($raw === '') {
            return null;
        }

        if ($raw[0] !== '/') {
            $raw = '/' . $raw;
        }

        if (strlen($raw) > 1) {
            $raw = rtrim($raw, '/');
            if ($raw === '') {
                $raw = '/';
            }
        }

        return strlen($raw) <= 255 ? $raw : null;
    }

    /** @param list<string> $cols */
    private function isHeader(array $cols): bool
    {
        if (!isset($cols[0])) {
            return false;
        }

        return in_array(strtolower(trim($cols[0])), self::HEADER_KEYS, true);
    }

    /** @return list<list<string>> */
    private function rows(string $csv): array
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return [];
        }

        fwrite($stream, $csv);
        rewind($stream);

        $rows = [];
        // Empty $escape is the modern, non-deprecated CSV behaviour (PHP 8.4).
        while (($cols = fgetcsv($stream, null, ',', '"', '')) !== false) {
            $strCols = array_map(
                static fn (?string $c): string => $c ?? '',
                $cols,
            );

            if (implode('', $strCols) === '') {
                continue;
            }

            $rows[] = $strCols;
            if (count($rows) > self::MAX_ROWS + 2) {
                break;
            }
        }

        fclose($stream);

        return $rows;
    }
}
