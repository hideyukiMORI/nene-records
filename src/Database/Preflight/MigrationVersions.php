<?php

declare(strict_types=1);

namespace NeNeRecords\Database\Preflight;

/**
 * The Phinx migration version ids this application ships — the numeric prefix of each
 * `database/migrations/<version>_*.php` file.
 *
 * Fed to the framework's `DefaultDatabaseCandidateInspector` so machine database
 * preflight (#648) can classify a candidate's ledger as fresh / compatible / ahead /
 * foreign / partial. An empty list makes the inspector return `needs_review`
 * (`migration_versions_unknown`), so the real set is always passed.
 */
final class MigrationVersions
{
    /**
     * Known migration versions, ascending. Empty when the directory is absent.
     * Pass an explicit $directory only in tests; production reads the shipped folder.
     *
     * @return list<string>
     */
    public static function known(?string $directory = null): array
    {
        $directory ??= dirname(__DIR__, 3) . '/database/migrations';

        if (!is_dir($directory)) {
            return [];
        }

        $entries = scandir($directory);

        if ($entries === false) {
            return [];
        }

        $versions = [];
        foreach ($entries as $entry) {
            if (preg_match('/^(\d+)_.*\.php$/', $entry, $matches) === 1) {
                $versions[] = $matches[1];
            }
        }

        sort($versions);

        return $versions;
    }
}
