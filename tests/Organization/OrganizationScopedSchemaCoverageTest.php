<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization;

use NeNeRecords\Organization\OrganizationScopedSchema;
use PHPUnit\Framework\TestCase;

/**
 * Guards the org-scoped table list against schema drift (#1002).
 *
 * Deleting an organization purges every table carrying `organization_id`. A new table with
 * that column is therefore a silent orphan factory unless it is added to
 * {@see OrganizationScopedSchema::ORG_SCOPED_TABLES} — this test is what makes that
 * omission loud. `database/schema/*.sql` is the shipped shape the suite builds from, so it
 * is the reference the list is compared against.
 */
final class OrganizationScopedSchemaCoverageTest extends TestCase
{
    public function testEveryTableCarryingOrganizationIdIsPurged(): void
    {
        $fromSchema = self::tablesWithOrganizationId();

        self::assertNotSame([], $fromSchema, 'No schema fixture declared organization_id — the parser is wrong.');

        $covered = OrganizationScopedSchema::ORG_SCOPED_TABLES;
        sort($covered);

        self::assertSame(
            $fromSchema,
            $covered,
            'OrganizationScopedSchema::ORG_SCOPED_TABLES drifted from database/schema/*.sql. '
            . 'A table with organization_id that is not purged leaves orphans when an org is deleted (#1002).',
        );
    }

    public function testDerivedPurgesBindOneOrgIdPerPlaceholder(): void
    {
        foreach (OrganizationScopedSchema::DERIVED_PURGES as [$table, $where]) {
            self::assertGreaterThan(
                0,
                substr_count($where, '?'),
                "Derived purge for {$table} binds no org id — it would delete every row.",
            );
            self::assertStringContainsString(
                'organization_id = ?',
                $where,
                "Derived purge for {$table} must scope its subquery by organization_id.",
            );
        }
    }

    /**
     * Table names declared with an `organization_id` column across the schema fixtures.
     *
     * @return list<string>
     */
    private static function tablesWithOrganizationId(): array
    {
        $tables = [];

        foreach (SchemaFixtures::files() as $path) {
            $raw = (string) file_get_contents($path);

            preg_match_all(
                '/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?(\w+)\s*\((.*?)\n\)\s*;/s',
                $raw,
                $matches,
                PREG_SET_ORDER,
            );

            foreach ($matches as $match) {
                if (preg_match('/\borganization_id\b/', $match[2]) === 1) {
                    $tables[] = $match[1];
                }
            }
        }

        sort($tables);

        return $tables;
    }
}
