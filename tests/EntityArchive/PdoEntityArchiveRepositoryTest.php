<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityArchive;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Http\RequestScopedHolder;
use Nene2\Http\UtcClock;
use NeNeRecords\EntityArchive\PdoEntityArchiveRepository;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\Tests\Organization\SchemaFixtures;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the archive repository against a real schema (#1017).
 *
 * There was no such test before, and that is precisely why the bug survived: every test that
 * reached `archiveAndPurgeSoftDeleted()` went through `InMemoryEntityArchiveRepository`, so
 * **nothing ever executed the INSERT**. `entity_archive` had no migration and did not exist in
 * any real database, and deleting an entity type that owned a soft-deleted entity returned 500
 * with `no such table: entity_archive`. Green tests, working code path, absent table.
 *
 * The schema here is built from every snapshot in `database/schema/`, so a table that stops
 * existing again fails these tests rather than only production.
 */
final class PdoEntityArchiveRepositoryTest extends TestCase
{
    private const ORG = 7;
    private const OTHER_ORG = 8;

    private PdoDatabaseQueryExecutor $executor;
    private PdoEntityArchiveRepository $repository;

    /** @var RequestScopedHolder<int> */
    private RequestScopedHolder $orgId;

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

        $this->orgId = new RequestScopedHolder();
        $this->orgId->set(self::ORG);

        $this->repository = new PdoEntityArchiveRepository($this->executor, $this->orgId, new UtcClock());
    }

    public function testSoftDeletedEntitiesAreArchivedThenPurged(): void
    {
        $this->seedEntityType(1, self::ORG, 'note');
        $this->seedSoftDeletedEntity(10, self::ORG, 1, 'gone');
        $this->executor->execute(
            'INSERT INTO text_fields (organization_id, entity_id, field_key, value) VALUES (?, 10, ?, ?)',
            [self::ORG, 'body', 'farewell'],
        );

        $this->repository->archiveAndPurgeSoftDeleted(new EntityType(name: 'Note', slug: 'note', id: 1));

        $archived = $this->executor->fetchOne('SELECT * FROM entity_archive WHERE original_entity_id = 10');

        self::assertNotNull($archived, 'The soft-deleted entity was not archived.');
        self::assertSame(self::ORG, (int) $archived['organization_id'], 'The archive row is not org-scoped.');
        self::assertSame('note', $archived['entity_type_slug']);
        self::assertSame('entity_type_deleted', $archived['archived_reason']);

        $snapshot = json_decode((string) $archived['snapshot'], true);

        self::assertIsArray($snapshot);
        self::assertSame([['field_key' => 'body', 'value' => 'farewell']], $snapshot['text_fields']);

        self::assertSame(0, $this->rowCount('entities', 'id = 10'), 'The entity was archived but not purged.');
        self::assertSame(0, $this->rowCount('text_fields', 'entity_id = 10'), 'Field values outlived their entity.');
    }

    public function testActiveEntitiesAreLeftAlone(): void
    {
        $this->seedEntityType(1, self::ORG, 'note');
        $this->seedSoftDeletedEntity(10, self::ORG, 1, 'gone');
        $this->executor->execute(
            "INSERT INTO entities (id, organization_id, entity_type_id, slug, status, is_deleted)
             VALUES (11, ?, 1, 'kept', 'published', 0)",
            [self::ORG],
        );

        $this->repository->archiveAndPurgeSoftDeleted(new EntityType(name: 'Note', slug: 'note', id: 1));

        self::assertSame(0, $this->rowCount('entity_archive', 'original_entity_id = 11'));
        self::assertSame(1, $this->rowCount('entities', 'id = 11'), 'A live entity was purged.');
    }

    public function testAnotherOrganizationsSoftDeletedEntityIsUntouched(): void
    {
        $this->seedEntityType(1, self::ORG, 'note');
        $this->seedEntityType(2, self::OTHER_ORG, 'note');
        $this->seedSoftDeletedEntity(20, self::OTHER_ORG, 2, 'theirs');

        $this->repository->archiveAndPurgeSoftDeleted(new EntityType(name: 'Note', slug: 'note', id: 1));

        self::assertSame(0, $this->rowCount('entity_archive', '1 = 1'), 'Another org\'s entity was archived.');
        self::assertSame(1, $this->rowCount('entities', 'id = 20'), 'Another org\'s entity was purged.');
    }

    public function testNothingHappensWhenThereIsNoSoftDeletedEntity(): void
    {
        $this->seedEntityType(1, self::ORG, 'note');

        $this->repository->archiveAndPurgeSoftDeleted(new EntityType(name: 'Note', slug: 'note', id: 1));

        self::assertSame(0, $this->rowCount('entity_archive', '1 = 1'));
    }

    private function seedEntityType(int $id, int $org, string $slug): void
    {
        $this->executor->execute(
            'INSERT INTO entity_types (id, organization_id, slug, name) VALUES (?, ?, ?, ?)',
            [$id, $org, $slug, ucfirst($slug)],
        );
    }

    private function seedSoftDeletedEntity(int $id, int $org, int $typeId, string $slug): void
    {
        $this->executor->execute(
            "INSERT INTO entities (id, organization_id, entity_type_id, slug, status, is_deleted, deleted_at)
             VALUES (?, ?, ?, ?, 'draft', 1, '2026-01-02 00:00:00')",
            [$id, $org, $typeId, $slug],
        );
    }

    private function rowCount(string $table, string $where): int
    {
        $row = $this->executor->fetchOne("SELECT COUNT(*) AS cnt FROM {$table} WHERE {$where}");

        return (int) ($row['cnt'] ?? 0);
    }
}
