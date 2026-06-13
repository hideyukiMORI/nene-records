<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityListCriteria;
use NeNeRecords\Entity\PdoEntityRepository;
use PHPUnit\Framework\TestCase;

final class PdoEntityRepositoryTest extends TestCase
{
    private PdoDatabaseQueryExecutor $executor;

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

        $this->executor->execute('PRAGMA foreign_keys = ON');
        $this->orgId = new RequestScopedHolder();
        $this->orgId->set(0);

        foreach ($this->schemaStatements() as $statement) {
            $this->executor->execute($statement);
        }
    }

    /**
     * @return list<string>
     */
    private function schemaStatements(): array
    {
        $projectRoot = dirname(__DIR__, 2);
        $paths = [
            $projectRoot . '/database/schema/entity_types.sql',
            $projectRoot . '/database/schema/entities.sql',
            $projectRoot . '/database/schema/entity_revisions.sql',
            $projectRoot . '/database/schema/tags.sql',
            $projectRoot . '/database/schema/entity_tags.sql',
            $projectRoot . '/database/schema/entity_relations.sql',
            $projectRoot . '/database/schema/text_fields.sql',
        ];

        $statements = [];

        foreach ($paths as $path) {
            self::assertFileExists($path);
            $raw = trim((string) file_get_contents($path));
            foreach (preg_split('/;\R/s', $raw) ?: [] as $chunk) {
                $statement = trim($chunk);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
            }
        }

        return $statements;
    }

    private function insertEntityTypeId(): int
    {
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('Base', 'base')");

        return $this->executor->lastInsertId();
    }

    public function testFindByIdReturnsEntity(): void
    {
        $typeId = $this->insertEntityTypeId();

        $this->executor->execute('INSERT INTO entities (entity_type_id) VALUES (?)', [$typeId]);

        $repository = new PdoEntityRepository($this->executor, $this->orgId);
        $entity = $repository->findById(1);

        self::assertNotNull($entity);
        self::assertSame(1, $entity->id);
        self::assertSame($typeId, $entity->entityTypeId);
        self::assertFalse($entity->isDeleted);
    }

    public function testFindByIdReturnsNullWhenSoftDeleted(): void
    {
        $typeId = $this->insertEntityTypeId();
        $this->executor->execute('INSERT INTO entities (entity_type_id) VALUES (?)', [$typeId]);

        $repository = new PdoEntityRepository($this->executor, $this->orgId);
        $repository->softDelete(1);

        self::assertNull($repository->findById(1));
    }

    public function testSoftDeleteAllowsRowToRemainInStorage(): void
    {
        $typeId = $this->insertEntityTypeId();
        $this->executor->execute('INSERT INTO entities (entity_type_id) VALUES (?)', [$typeId]);

        $repository = new PdoEntityRepository($this->executor, $this->orgId);
        $repository->softDelete(1);

        $row = $this->executor->fetchOne(
            'SELECT id, is_deleted FROM entities WHERE id = ?',
            [1],
        );

        self::assertIsArray($row);
        self::assertSame(1, (int) $row['is_deleted']);
    }

    public function testSaveReturnsNewId(): void
    {
        $typeId = $this->insertEntityTypeId();

        $repository = new PdoEntityRepository($this->executor, $this->orgId);
        $id = $repository->save(new Entity(id: null, entityTypeId: $typeId));

        self::assertSame(1, $id);
    }

    public function testSavedEntityIsRetrievableById(): void
    {
        $typeId = $this->insertEntityTypeId();

        $repository = new PdoEntityRepository($this->executor, $this->orgId);
        $id = $repository->save(new Entity(id: null, entityTypeId: $typeId));
        $entity = $repository->findById($id);

        self::assertNotNull($entity);
        self::assertSame($typeId, $entity->entityTypeId);
    }

    public function testFindAllOmitsDeletedRows(): void
    {
        $typeId = $this->insertEntityTypeId();

        $repository = new PdoEntityRepository($this->executor, $this->orgId);
        $a = $repository->save(new Entity(id: null, entityTypeId: $typeId));
        $repository->save(new Entity(id: null, entityTypeId: $typeId));

        $repository->softDelete($a);

        $list = $repository->findAll(10, 0);

        self::assertCount(1, $list);
        self::assertSame(2, $list[0]->id);
    }

    public function testFindAllRespectsLimitAndOffset(): void
    {
        $typeId = $this->insertEntityTypeId();

        $repository = new PdoEntityRepository($this->executor, $this->orgId);

        for ($i = 0; $i < 5; $i++) {
            $repository->save(new Entity(id: null, entityTypeId: $typeId));
        }

        $list = $repository->findAll(2, 2);

        // findAll は id DESC 順（新しい順）。ids: 5,4,3,2,1 → offset=2,limit=2 → [3,2]
        self::assertCount(2, $list);
        self::assertSame(3, $list[0]->id);
        self::assertSame(2, $list[1]->id);
    }

    public function testFindByCriteriaFiltersByEntityTypeId(): void
    {
        $typeA = $this->insertEntityTypeId();
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('Other', 'other')");
        $typeB = 2;

        $repository = new PdoEntityRepository($this->executor, $this->orgId);
        $repository->save(new Entity(id: null, entityTypeId: $typeA));
        $repository->save(new Entity(id: null, entityTypeId: $typeB));

        $list = $repository->findByCriteria(new EntityListCriteria(entityTypeId: $typeB), 10, 0);

        self::assertCount(1, $list);
        self::assertSame($typeB, $list[0]->entityTypeId);
        self::assertSame(1, $repository->countByCriteria(new EntityListCriteria(entityTypeId: $typeB)));
    }

    public function testFindByCriteriaFiltersByPublishedDateRange(): void
    {
        $typeId = $this->insertEntityTypeId();
        $repository = new PdoEntityRepository($this->executor, $this->orgId);

        $may = $repository->save(new Entity(
            id: null,
            entityTypeId: $typeId,
            publishedAt: new \DateTimeImmutable('2026-05-15T09:00:00+00:00'),
        ));
        $repository->save(new Entity(
            id: null,
            entityTypeId: $typeId,
            publishedAt: new \DateTimeImmutable('2026-06-20T23:30:00+00:00'),
        ));

        // June only: exclusive upper bound (2026-07-01) still matches a late-June time.
        $criteria = new EntityListCriteria(
            publishedFrom: '2026-06-01',
            publishedToExclusive: '2026-07-01',
        );
        $june = $repository->findByCriteria($criteria, 10, 0);

        self::assertCount(1, $june);
        self::assertNotSame($may, $june[0]->id);
        self::assertSame(1, $repository->countByCriteria($criteria));
    }

    public function testFindByCriteriaFiltersByTagSlugsWithOrSemantics(): void
    {
        $typeId = $this->insertEntityTypeId();

        $repository = new PdoEntityRepository($this->executor, $this->orgId);
        $entityA = $repository->save(new Entity(id: null, entityTypeId: $typeId));
        $entityB = $repository->save(new Entity(id: null, entityTypeId: $typeId));
        $repository->save(new Entity(id: null, entityTypeId: $typeId));

        $this->executor->execute("INSERT INTO tags (slug, name) VALUES ('featured', 'Featured')");
        $this->executor->execute("INSERT INTO tags (slug, name) VALUES ('draft', 'Draft')");
        $this->executor->execute('INSERT INTO entity_tags (entity_id, tag_id) VALUES (?, 1)', [$entityA]);
        $this->executor->execute('INSERT INTO entity_tags (entity_id, tag_id) VALUES (?, 2)', [$entityB]);

        $criteria = new EntityListCriteria(tagSlugs: ['featured', 'draft']);
        $list = $repository->findByCriteria($criteria, 10, 0);

        self::assertCount(2, $list);
        self::assertSame(2, $repository->countByCriteria($criteria));
    }

    public function testFindByCriteriaFiltersByRelationFieldWithAndSemantics(): void
    {
        $typeId = $this->insertEntityTypeId();

        $repository = new PdoEntityRepository($this->executor, $this->orgId);
        $entityA = $repository->save(new Entity(id: null, entityTypeId: $typeId));
        $entityB = $repository->save(new Entity(id: null, entityTypeId: $typeId));
        $repository->save(new Entity(id: null, entityTypeId: $typeId));
        $targetAuthor = $repository->save(new Entity(id: null, entityTypeId: $typeId));
        $targetCategory = $repository->save(new Entity(id: null, entityTypeId: $typeId));

        $this->executor->execute(
            'INSERT INTO entity_relations (source_entity_id, target_entity_id, field_key) VALUES (?, ?, ?)',
            [$entityA, $targetAuthor, 'author'],
        );
        $this->executor->execute(
            'INSERT INTO entity_relations (source_entity_id, target_entity_id, field_key) VALUES (?, ?, ?)',
            [$entityA, $targetCategory, 'category'],
        );
        $this->executor->execute(
            'INSERT INTO entity_relations (source_entity_id, target_entity_id, field_key) VALUES (?, ?, ?)',
            [$entityB, $targetAuthor, 'author'],
        );

        $singleFilter = new EntityListCriteria(relationFilters: ['author' => $targetAuthor]);
        $singleList = $repository->findByCriteria($singleFilter, 10, 0);

        self::assertCount(2, $singleList);
        self::assertSame(2, $repository->countByCriteria($singleFilter));

        $andFilter = new EntityListCriteria(relationFilters: ['author' => $targetAuthor, 'category' => $targetCategory]);
        $andList = $repository->findByCriteria($andFilter, 10, 0);

        self::assertCount(1, $andList);
        self::assertSame($entityA, $andList[0]->id);
        self::assertSame(1, $repository->countByCriteria($andFilter));
    }

    public function testFindByCriteriaSearchBySlug(): void
    {
        $typeId = $this->insertEntityTypeId();
        $repository = new PdoEntityRepository($this->executor, $this->orgId);

        $entityA = $repository->save(new Entity(id: null, entityTypeId: $typeId, slug: 'hello-world'));
        $repository->save(new Entity(id: null, entityTypeId: $typeId, slug: 'another-post'));

        $list = $repository->findByCriteria(new EntityListCriteria(q: 'hello'), 10, 0);

        self::assertCount(1, $list);
        self::assertSame($entityA, $list[0]->id);
        self::assertSame(1, $repository->countByCriteria(new EntityListCriteria(q: 'hello')));
    }

    public function testFindByCriteriaSearchByTextField(): void
    {
        $typeId = $this->insertEntityTypeId();
        $repository = new PdoEntityRepository($this->executor, $this->orgId);

        $entityA = $repository->save(new Entity(id: null, entityTypeId: $typeId));
        $entityB = $repository->save(new Entity(id: null, entityTypeId: $typeId));

        $this->executor->execute(
            'INSERT INTO text_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$entityA, 'title', 'Welcome to NeNe Records'],
        );
        $this->executor->execute(
            'INSERT INTO text_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$entityB, 'title', 'Another article'],
        );

        $list = $repository->findByCriteria(new EntityListCriteria(q: 'NeNe'), 10, 0);

        self::assertCount(1, $list);
        self::assertSame($entityA, $list[0]->id);
        self::assertSame(1, $repository->countByCriteria(new EntityListCriteria(q: 'NeNe')));
    }

    public function testFindByCriteriaSearchReturnsEmptyWhenNoMatch(): void
    {
        $typeId = $this->insertEntityTypeId();
        $repository = new PdoEntityRepository($this->executor, $this->orgId);

        $repository->save(new Entity(id: null, entityTypeId: $typeId, slug: 'hello-world'));

        $list = $repository->findByCriteria(new EntityListCriteria(q: 'zzznomatch'), 10, 0);

        self::assertCount(0, $list);
        self::assertSame(0, $repository->countByCriteria(new EntityListCriteria(q: 'zzznomatch')));
    }

    public function testFindByCriteriaSearchCombinesWithEntityTypeId(): void
    {
        $typeId = $this->insertEntityTypeId();
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('Other', 'other')");
        $otherTypeId = $this->executor->lastInsertId();

        $repository = new PdoEntityRepository($this->executor, $this->orgId);
        $entityA = $repository->save(new Entity(id: null, entityTypeId: $typeId, slug: 'hello-world'));
        $repository->save(new Entity(id: null, entityTypeId: $otherTypeId, slug: 'hello-other'));

        $list = $repository->findByCriteria(
            new EntityListCriteria(entityTypeId: $typeId, q: 'hello'),
            10,
            0,
        );

        self::assertCount(1, $list);
        self::assertSame($entityA, $list[0]->id);
    }
}
