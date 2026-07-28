<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization;

/**
 * Locates and loads the schema snapshots under `database/schema/`.
 *
 * The snapshots are the documented mirror of the migrations' end state
 * (`docs/development/backend-standards.md`), and all but two are already SQLite-parsable.
 * The two exceptions are handled here rather than by hand-writing replacement tables, so
 * a column added to a snapshot still reaches the tests that build from it:
 *
 * - `users.sql` declares `status ENUM('active', 'invited')`. SQLite has no ENUM, so it is
 *   rewritten to a VARCHAR of equivalent width — the tests never assert on the constraint.
 * - `entity_archive.sql` is full MySQL DDL (`INT UNSIGNED … AUTO_INCREMENT`, `ENGINE=InnoDB`)
 *   and is skipped outright. No migration creates that table, so it is absent from real
 *   databases as well (see OrganizationScopedSchema).
 */
final class SchemaFixtures
{
    /** Snapshots that cannot be executed on SQLite at all. */
    private const NOT_EXECUTABLE = ['entity_archive.sql'];

    /**
     * Every snapshot file, including the ones that cannot be executed — callers that read
     * the DDL as text (coverage checks) still need to see them.
     *
     * @return list<string> absolute paths, sorted
     */
    public static function files(): array
    {
        $paths = glob(dirname(__DIR__, 2) . '/database/schema/*.sql') ?: [];
        sort($paths);

        return $paths;
    }

    /**
     * Every executable DDL statement across the snapshots, ready to run one by one.
     *
     * @return list<string>
     */
    public static function statements(): array
    {
        $statements = [];

        foreach (self::files() as $path) {
            if (in_array(basename($path), self::NOT_EXECUTABLE, true)) {
                continue;
            }

            $raw = trim((string) file_get_contents($path));
            $raw = (string) preg_replace('/\bENUM\s*\([^)]*\)/i', 'VARCHAR(32)', $raw);

            foreach (preg_split('/;\R/s', $raw) ?: [] as $chunk) {
                $statement = trim(rtrim(trim($chunk), ';'));

                if ($statement !== '') {
                    $statements[] = $statement;
                }
            }
        }

        return $statements;
    }
}
