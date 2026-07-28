<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization;

/**
 * Locates and loads the schema snapshots under `database/schema/`.
 *
 * The snapshots are the documented mirror of the migrations' end state
 * (`docs/development/backend-standards.md`), and **every one of them runs on SQLite**, so the
 * tests build from the shipped shape rather than from hand-written replacement tables — a
 * column added to a snapshot reaches them automatically.
 *
 * One dialect gap is bridged here: `users.sql` declares `status ENUM('active', 'invited')` and
 * SQLite has no ENUM, so it is rewritten to a VARCHAR of equivalent width. No test asserts on
 * that constraint.
 *
 * (`entity_archive.sql` used to be skipped as unexecutable MySQL DDL — it was the one snapshot
 * no migration backed, and its absence from real databases was the #1017 bug. The migration
 * now exists and the snapshot was rewritten in the same dialect as the rest.)
 */
final class SchemaFixtures
{
    /**
     * Every snapshot file.
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
     * Every DDL statement across the snapshots, ready to run one by one.
     *
     * @return list<string>
     */
    public static function statements(): array
    {
        $statements = [];

        foreach (self::files() as $path) {
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
