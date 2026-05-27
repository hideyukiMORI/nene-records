<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\IntField;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\IntField\IntField;
use NeNeRecords\IntField\IntFieldNotFoundException;
use NeNeRecords\IntField\PdoIntFieldRepository;
use PHPUnit\Framework\TestCase;

final class PdoIntFieldRepositoryTest extends TestCase
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
            $projectRoot . '/database/schema/int_fields.sql',
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

    /** @return array{entityTypeId: int, entityId: int} */
    private function insertEntityHierarchy(): array
    {
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('Base', 'base')");
        $entityTypeId = $this->executor->lastInsertId();

        $this->executor->execute('INSERT INTO entities (entity_type_id) VALUES (?)', [$entityTypeId]);
        $entityId = $this->executor->lastInsertId();

        return ['entityTypeId' => $entityTypeId, 'entityId' => $entityId];
    }

    public function testFindByIdReturnsIntField(): void
    {
        $ids = $this->insertEntityHierarchy();

        $this->executor->execute(
            'INSERT INTO int_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$ids['entityId'], 'count', 42],
        );

        $repository = new PdoIntFieldRepository($this->executor, $this->orgId);
        $intField = $repository->findById(1);

        self::assertNotNull($intField);
        self::assertSame(1, $intField->id);
        self::assertSame($ids['entityId'], $intField->entityId);
        self::assertSame('count', $intField->fieldKey);
        self::assertSame(42, $intField->value);
    }

    public function testFindByIdReturnsNullAfterSoftDelete(): void
    {
        $ids = $this->insertEntityHierarchy();

        $this->executor->execute(
            'INSERT INTO int_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$ids['entityId'], 'count', 7],
        );

        $repository = new PdoIntFieldRepository($this->executor, $this->orgId);
        $repository->delete(1);

        self::assertNull($repository->findById(1));
    }

    public function testSaveReturnsNewId(): void
    {
        $ids = $this->insertEntityHierarchy();

        $repository = new PdoIntFieldRepository($this->executor, $this->orgId);
        $id = $repository->save(new IntField(entityId: $ids['entityId'], fieldKey: 'k', value: 3));

        self::assertSame(1, $id);
    }

    public function testUpdateThrowsWhenRowIsSoftDeleted(): void
    {
        $ids = $this->insertEntityHierarchy();

        $this->executor->execute(
            'INSERT INTO int_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$ids['entityId'], 'count', 1],
        );

        $repository = new PdoIntFieldRepository($this->executor, $this->orgId);
        $repository->delete(1);

        $this->expectException(IntFieldNotFoundException::class);

        $repository->update(new IntField(entityId: $ids['entityId'], fieldKey: 'count', value: 2, id: 1));
    }

    public function testFindAllExcludesDeleted(): void
    {
        $ids = $this->insertEntityHierarchy();

        $repository = new PdoIntFieldRepository($this->executor, $this->orgId);

        $repository->save(new IntField(entityId: $ids['entityId'], fieldKey: 'a', value: 1));
        $b = $repository->save(new IntField(entityId: $ids['entityId'], fieldKey: 'b', value: 2));

        $repository->delete($b);

        $list = $repository->findAll(10, 0);

        self::assertCount(1, $list);
        self::assertSame('a', $list[0]->fieldKey);
    }
}
