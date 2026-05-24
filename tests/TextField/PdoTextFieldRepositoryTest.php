<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\TextField;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeRecords\TextField\PdoTextFieldRepository;
use NeNeRecords\TextField\TextField;
use NeNeRecords\TextField\TextFieldNotFoundException;
use PHPUnit\Framework\TestCase;

final class PdoTextFieldRepositoryTest extends TestCase
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

    /** @return array{entityTypeId: int, entityId: int} */
    private function insertEntityHierarchy(): array
    {
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('Base', 'base')");
        $entityTypeId = $this->executor->lastInsertId();

        $this->executor->execute('INSERT INTO entities (entity_type_id) VALUES (?)', [$entityTypeId]);
        $entityId = $this->executor->lastInsertId();

        return ['entityTypeId' => $entityTypeId, 'entityId' => $entityId];
    }

    public function testFindByIdReturnsTextField(): void
    {
        $ids = $this->insertEntityHierarchy();

        $this->executor->execute(
            'INSERT INTO text_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$ids['entityId'], 'title', 'Hi'],
        );

        $repository = new PdoTextFieldRepository($this->executor);
        $textField = $repository->findById(1);

        self::assertNotNull($textField);
        self::assertSame(1, $textField->id);
        self::assertSame($ids['entityId'], $textField->entityId);
        self::assertSame('title', $textField->fieldKey);
        self::assertSame('Hi', $textField->value);
    }

    public function testFindByIdReturnsNullAfterSoftDelete(): void
    {
        $ids = $this->insertEntityHierarchy();

        $this->executor->execute(
            'INSERT INTO text_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$ids['entityId'], 'body', 'X'],
        );

        $repository = new PdoTextFieldRepository($this->executor);
        $repository->delete(1);

        self::assertNull($repository->findById(1));
    }

    public function testSaveReturnsNewId(): void
    {
        $ids = $this->insertEntityHierarchy();

        $repository = new PdoTextFieldRepository($this->executor);
        $id = $repository->save(new TextField(entityId: $ids['entityId'], fieldKey: 'k', value: 'v'));

        self::assertSame(1, $id);
    }

    public function testUpdateThrowsWhenRowIsSoftDeleted(): void
    {
        $ids = $this->insertEntityHierarchy();

        $this->executor->execute(
            'INSERT INTO text_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$ids['entityId'], 'note', 'A'],
        );

        $repository = new PdoTextFieldRepository($this->executor);
        $repository->delete(1);

        $this->expectException(TextFieldNotFoundException::class);

        $repository->update(new TextField(entityId: $ids['entityId'], fieldKey: 'note', value: 'B', id: 1));
    }

    public function testFindAllExcludesDeleted(): void
    {
        $ids = $this->insertEntityHierarchy();

        $repository = new PdoTextFieldRepository($this->executor);

        $repository->save(new TextField(entityId: $ids['entityId'], fieldKey: 'a', value: '1'));
        $b = $repository->save(new TextField(entityId: $ids['entityId'], fieldKey: 'b', value: '2'));

        $repository->delete($b);

        $list = $repository->findAll(10, 0);

        self::assertCount(1, $list);
        self::assertSame('a', $list[0]->fieldKey);
    }
}
