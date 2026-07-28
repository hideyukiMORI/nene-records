<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Http\UtcClock;
use NeNeRecords\Organization\OrganizationNotFoundException;
use NeNeRecords\Organization\OrganizationScopedSchema;
use NeNeRecords\Organization\PdoOrganizationRepository;
use PHPUnit\Framework\TestCase;

/**
 * Deleting an organization must leave nothing of it behind (#1002).
 *
 * Production measured the opposite on 2026-07-23: `DELETE FROM organizations` removed org 7
 * and left 13 `entities` rows (plus 25 other tables) orphaned, because `organization_id`
 * carries no foreign key. Every assertion here fails if the purge regresses to that.
 *
 * Two organizations are seeded so the tests prove the purge is *scoped* — deleting one must
 * not touch the other. A purge that forgot its WHERE clause would pass a residue-only check.
 */
final class PdoOrganizationRepositoryDeleteTest extends TestCase
{
    private const VICTIM = 1;
    private const BYSTANDER = 2;

    private PdoDatabaseQueryExecutor $executor;
    private PdoOrganizationRepository $repository;
    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->executor = new PdoDatabaseQueryExecutor(new PdoConnectionFactory(new DatabaseConfig(
            null,
            'test',
            'sqlite',
            'localhost',
            1,
            ':memory:',
            'nene-records-test',
            '',
            'utf8',
        )));

        foreach (SchemaFixtures::statements() as $statement) {
            $this->executor->execute($statement);
        }

        $this->repository = new PdoOrganizationRepository($this->executor, new UtcClock());

        $this->seedOrganization(self::VICTIM);
        $this->seedOrganization(self::BYSTANDER);
    }

    public function testDeleteLeavesNoRowScopedToTheOrganization(): void
    {
        // Proves the assertions below are not vacuous: every table really did hold a row.
        foreach (OrganizationScopedSchema::ORG_SCOPED_TABLES as $table) {
            self::assertSame(1, $this->countByOrg($table, self::VICTIM), "{$table} was not seeded.");
        }

        $this->repository->delete(self::VICTIM);

        foreach (OrganizationScopedSchema::ORG_SCOPED_TABLES as $table) {
            self::assertSame(
                0,
                $this->countByOrg($table, self::VICTIM),
                "{$table} still holds rows for the deleted organization (#1002).",
            );
        }

        self::assertNull($this->repository->findById(self::VICTIM));
    }

    public function testDeleteAlsoPurgesTablesWithoutAnOrganizationIdColumn(): void
    {
        $this->repository->delete(self::VICTIM);

        self::assertSame(0, $this->countWhere('entity_tags', 'entity_id = ?', $this->entityId(self::VICTIM)));
        self::assertSame(
            0,
            $this->countWhere('entity_relations', 'source_entity_id = ?', $this->entityId(self::VICTIM)),
        );
        self::assertSame(0, $this->countWhere('webhook_deliveries', 'webhook_id = ?', $this->webhookId(self::VICTIM)));
        self::assertSame(0, $this->countWhere('user_profiles', 'user_id = ?', $this->userId(self::VICTIM)));
    }

    public function testDeleteLeavesOtherOrganizationsUntouched(): void
    {
        $this->repository->delete(self::VICTIM);

        foreach (OrganizationScopedSchema::ORG_SCOPED_TABLES as $table) {
            self::assertSame(
                1,
                $this->countByOrg($table, self::BYSTANDER),
                "{$table} lost the surviving organization's row — the purge is not org-scoped.",
            );
        }

        self::assertSame(1, $this->countWhere('entity_tags', 'entity_id = ?', $this->entityId(self::BYSTANDER)));
        self::assertSame(
            1,
            $this->countWhere('entity_relations', 'source_entity_id = ?', $this->entityId(self::BYSTANDER)),
        );
        self::assertSame(
            1,
            $this->countWhere('webhook_deliveries', 'webhook_id = ?', $this->webhookId(self::BYSTANDER)),
        );
        self::assertSame(1, $this->countWhere('user_profiles', 'user_id = ?', $this->userId(self::BYSTANDER)));
        self::assertNotNull($this->repository->findById(self::BYSTANDER));
    }

    public function testDeleteRejectsAnUnknownOrganizationBeforeTouchingAnything(): void
    {
        try {
            $this->repository->delete(999);
            self::fail('Expected OrganizationNotFoundException.');
        } catch (OrganizationNotFoundException) {
            // expected
        }

        self::assertSame(1, $this->countByOrg('entities', self::VICTIM));
        self::assertSame(1, $this->countByOrg('entities', self::BYSTANDER));
    }

    /**
     * Seeds one row in every org-scoped table plus the four tables purged through a parent.
     *
     * Foreign keys are switched off for the seed only. The rows are generated generically
     * (see {@see insertRow()}), so FK columns get placeholder ids that need not resolve —
     * what matters is that the *delete* runs with enforcement on, which is where the
     * RESTRICT ordering is actually proven.
     */
    private function seedOrganization(int $org): void
    {
        $this->executor->execute('PRAGMA foreign_keys = OFF');

        $this->executor->execute(
            'INSERT INTO organizations (id, name, slug, plan, is_active, created_at, updated_at)'
            . " VALUES (?, ?, ?, 'free', 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
            [$org, "Org {$org}", "org-{$org}"],
        );

        foreach (OrganizationScopedSchema::ORG_SCOPED_TABLES as $table) {
            $this->insertRow($table, ['organization_id' => $org, 'id' => $this->rowId($org, $table)]);
        }

        $this->insertRow('entity_tags', ['entity_id' => $this->entityId($org)]);
        $this->insertRow('entity_relations', [
            'source_entity_id' => $this->entityId($org),
            'target_entity_id' => $this->entityId($org),
        ]);
        $this->insertRow('webhook_deliveries', [
            'webhook_id' => $this->webhookId($org),
            'entity_id' => $this->entityId($org),
        ]);
        $this->insertRow('user_profiles', ['user_id' => $this->userId($org)]);

        $this->executor->execute('PRAGMA foreign_keys = ON');
    }

    /**
     * Inserts one row, filling every NOT NULL column the caller did not supply.
     *
     * Generic on purpose: the point of the test is that *all* org-scoped tables are covered,
     * so hand-writing 26 inserts would rot the moment a column is added.
     *
     * @param array<string, int|string> $values
     */
    private function insertRow(string $table, array $values): void
    {
        foreach ($this->columns($table) as $column) {
            $name = (string) $column['name'];

            if (array_key_exists($name, $values)) {
                continue;
            }

            $isPrimaryKey = (int) $column['pk'] === 1;
            $isRequired = (int) $column['notnull'] === 1 && $column['dflt_value'] === null;

            if ($isPrimaryKey || !$isRequired) {
                continue;
            }

            $values[$name] = $this->placeholderFor((string) $column['type']);
        }

        $columns = implode(', ', array_keys($values));
        $marks = implode(', ', array_fill(0, count($values), '?'));

        $this->executor->execute(
            "INSERT INTO {$table} ({$columns}) VALUES ({$marks})",
            array_values($values),
        );
    }

    /**
     * A type-appropriate value that is distinct on every call.
     *
     * Distinctness matters: several snapshots carry UNIQUE indexes (`entity_preview_tokens.token`,
     * `users.email`, …), and seeding two organizations with a constant would collide on them.
     */
    private function placeholderFor(string $type): int|string
    {
        $seq = ++$this->sequence;
        $upper = strtoupper($type);

        if (str_contains($upper, 'DATE') || str_contains($upper, 'TIME')) {
            return '2026-01-01 00:00:00';
        }

        if (
            str_contains($upper, 'INT')
            || str_contains($upper, 'BOOL')
            || str_contains($upper, 'REAL')
            || str_contains($upper, 'FLOA')
            || str_contains($upper, 'DOUB')
        ) {
            return $seq;
        }

        return "seed-{$seq}";
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function columns(string $table): array
    {
        return $this->executor->fetchAll("PRAGMA table_info({$table})");
    }

    /** Stable, collision-free ids so the derived tables can point at the right parents. */
    private function rowId(int $org, string $table): int
    {
        $index = array_search($table, OrganizationScopedSchema::ORG_SCOPED_TABLES, true);

        return $org * 1000 + (is_int($index) ? $index : 0) + 1;
    }

    private function entityId(int $org): int
    {
        return $this->rowId($org, 'entities');
    }

    private function userId(int $org): int
    {
        return $this->rowId($org, 'users');
    }

    private function webhookId(int $org): int
    {
        return $this->rowId($org, 'webhooks');
    }

    private function countByOrg(string $table, int $org): int
    {
        return $this->countWhere($table, 'organization_id = ?', $org);
    }

    private function countWhere(string $table, string $where, int $value): int
    {
        $row = $this->executor->fetchOne("SELECT COUNT(*) AS cnt FROM {$table} WHERE {$where}", [$value]);

        return (int) ($row['cnt'] ?? 0);
    }
}
