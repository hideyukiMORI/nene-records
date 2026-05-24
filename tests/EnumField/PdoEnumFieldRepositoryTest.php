<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EnumField;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeRecords\EnumField\EnumField;
use NeNeRecords\EnumField\EnumFieldNotFoundException;
use NeNeRecords\EnumField\PdoEnumFieldRepository;
use PHPUnit\Framework\TestCase;

final class PdoEnumFieldRepositoryTest extends TestCase
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
            $projectRoot . '/database/schema/enum_fields.sql',
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

    public function testFindByIdReturnsEnumField(): void
    {
        $ids = $this->insertEntityHierarchy();

        $this->executor->execute(
            'INSERT INTO enum_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$ids['entityId'], 'count', 'active'],
        );

        $repository = new PdoEnumFieldRepository($this->executor);
        $enumField = $repository->findById(1);

        self::assertNotNull($enumField);
        self::assertSame(1, $enumField->id);
        self::assertSame($ids['entityId'], $enumField->entityId);
        self::assertSame('count', $enumField->fieldKey);
        self::assertSame('active', $enumField->value);
    }

    public function testFindByIdReturnsNullAfterSoftDelete(): void
    {
        $ids = $this->insertEntityHierarchy();

        $this->executor->execute(
            'INSERT INTO enum_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$ids['entityId'], 'count', 'active'],
        );

        $repository = new PdoEnumFieldRepository($this->executor);
        $repository->delete(1);

        self::assertNull($repository->findById(1));
    }

    public function testSaveReturnsNewId(): void
    {
        $ids = $this->insertEntityHierarchy();

        $repository = new PdoEnumFieldRepository($this->executor);
        $id = $repository->save(new EnumField(entityId: $ids['entityId'], fieldKey: 'k', value: 'active'));

        self::assertSame(1, $id);
    }

    public function testUpdateThrowsWhenRowIsSoftDeleted(): void
    {
        $ids = $this->insertEntityHierarchy();

        $this->executor->execute(
            'INSERT INTO enum_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$ids['entityId'], 'count', 'active'],
        );

        $repository = new PdoEnumFieldRepository($this->executor);
        $repository->delete(1);

        $this->expectException(EnumFieldNotFoundException::class);

        $repository->update(new EnumField(entityId: $ids['entityId'], fieldKey: 'count', value: 'inactive', id: 1));
    }

    public function testFindAllExcludesDeleted(): void
    {
        $ids = $this->insertEntityHierarchy();

        $repository = new PdoEnumFieldRepository($this->executor);

        $repository->save(new EnumField(entityId: $ids['entityId'], fieldKey: 'a', value: 'active'));
        $b = $repository->save(new EnumField(entityId: $ids['entityId'], fieldKey: 'b', value: 'inactive'));

        $repository->delete($b);

        $list = $repository->findAll(10, 0);

        self::assertCount(1, $list);
        self::assertSame('a', $list[0]->fieldKey);
    }
}
