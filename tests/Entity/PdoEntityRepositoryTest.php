<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityListCriteria;
use NeNeRecords\Entity\PdoEntityRepository;
use PHPUnit\Framework\TestCase;

final class PdoEntityRepositoryTest extends TestCase
{
    private PdoDatabaseQueryExecutor $executor;

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
            $projectRoot . '/database/schema/tags.sql',
            $projectRoot . '/database/schema/entity_tags.sql',
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

        $repository = new PdoEntityRepository($this->executor);
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

        $repository = new PdoEntityRepository($this->executor);
        $repository->softDelete(1);

        self::assertNull($repository->findById(1));
    }

    public function testSoftDeleteAllowsRowToRemainInStorage(): void
    {
        $typeId = $this->insertEntityTypeId();
        $this->executor->execute('INSERT INTO entities (entity_type_id) VALUES (?)', [$typeId]);

        $repository = new PdoEntityRepository($this->executor);
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

        $repository = new PdoEntityRepository($this->executor);
        $id = $repository->save(new Entity(id: null, entityTypeId: $typeId));

        self::assertSame(1, $id);
    }

    public function testSavedEntityIsRetrievableById(): void
    {
        $typeId = $this->insertEntityTypeId();

        $repository = new PdoEntityRepository($this->executor);
        $id = $repository->save(new Entity(id: null, entityTypeId: $typeId));
        $entity = $repository->findById($id);

        self::assertNotNull($entity);
        self::assertSame($typeId, $entity->entityTypeId);
    }

    public function testFindAllOmitsDeletedRows(): void
    {
        $typeId = $this->insertEntityTypeId();

        $repository = new PdoEntityRepository($this->executor);
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

        $repository = new PdoEntityRepository($this->executor);

        for ($i = 0; $i < 5; $i++) {
            $repository->save(new Entity(id: null, entityTypeId: $typeId));
        }

        $list = $repository->findAll(2, 2);

        self::assertCount(2, $list);
        self::assertSame(3, $list[0]->id);
        self::assertSame(4, $list[1]->id);
    }

    public function testFindByCriteriaFiltersByEntityTypeId(): void
    {
        $typeA = $this->insertEntityTypeId();
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('Other', 'other')");
        $typeB = 2;

        $repository = new PdoEntityRepository($this->executor);
        $repository->save(new Entity(id: null, entityTypeId: $typeA));
        $repository->save(new Entity(id: null, entityTypeId: $typeB));

        $list = $repository->findByCriteria(new EntityListCriteria(entityTypeId: $typeB), 10, 0);

        self::assertCount(1, $list);
        self::assertSame($typeB, $list[0]->entityTypeId);
        self::assertSame(1, $repository->countByCriteria(new EntityListCriteria(entityTypeId: $typeB)));
    }

    public function testFindByCriteriaFiltersByTagSlugsWithOrSemantics(): void
    {
        $typeId = $this->insertEntityTypeId();

        $repository = new PdoEntityRepository($this->executor);
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
}
