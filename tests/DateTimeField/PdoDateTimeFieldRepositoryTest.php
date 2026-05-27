<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\DateTimeField;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\DateTimeField\DateTimeField;
use NeNeRecords\DateTimeField\DateTimeFieldNotFoundException;
use NeNeRecords\DateTimeField\PdoDateTimeFieldRepository;
use PHPUnit\Framework\TestCase;

final class PdoDateTimeFieldRepositoryTest extends TestCase
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
            $projectRoot . '/database/schema/datetime_fields.sql',
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

    public function testFindByIdReturnsDateTimeField(): void
    {
        $ids = $this->insertEntityHierarchy();

        $this->executor->execute(
            'INSERT INTO datetime_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$ids['entityId'], 'count', '2026-05-24T12:00:00+00:00'],
        );

        $repository = new PdoDateTimeFieldRepository($this->executor, $this->orgId);
        $datetimeField = $repository->findById(1);

        self::assertNotNull($datetimeField);
        self::assertSame(1, $datetimeField->id);
        self::assertSame($ids['entityId'], $datetimeField->entityId);
        self::assertSame('count', $datetimeField->fieldKey);
        self::assertSame('2026-05-24T12:00:00+00:00', $datetimeField->value);
    }

    public function testFindByIdReturnsNullAfterSoftDelete(): void
    {
        $ids = $this->insertEntityHierarchy();

        $this->executor->execute(
            'INSERT INTO datetime_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$ids['entityId'], 'count', '2026-05-24T12:00:00+00:00'],
        );

        $repository = new PdoDateTimeFieldRepository($this->executor, $this->orgId);
        $repository->delete(1);

        self::assertNull($repository->findById(1));
    }

    public function testSaveReturnsNewId(): void
    {
        $ids = $this->insertEntityHierarchy();

        $repository = new PdoDateTimeFieldRepository($this->executor, $this->orgId);
        $id = $repository->save(new DateTimeField(entityId: $ids['entityId'], fieldKey: 'k', value: '2026-05-24T12:00:00+00:00'));

        self::assertSame(1, $id);
    }

    public function testUpdateThrowsWhenRowIsSoftDeleted(): void
    {
        $ids = $this->insertEntityHierarchy();

        $this->executor->execute(
            'INSERT INTO datetime_fields (entity_id, field_key, value) VALUES (?, ?, ?)',
            [$ids['entityId'], 'count', '2026-05-24T12:00:00+00:00'],
        );

        $repository = new PdoDateTimeFieldRepository($this->executor, $this->orgId);
        $repository->delete(1);

        $this->expectException(DateTimeFieldNotFoundException::class);

        $repository->update(new DateTimeField(entityId: $ids['entityId'], fieldKey: 'count', value: '2026-05-25T12:00:00+00:00', id: 1));
    }

    public function testFindAllExcludesDeleted(): void
    {
        $ids = $this->insertEntityHierarchy();

        $repository = new PdoDateTimeFieldRepository($this->executor, $this->orgId);

        $repository->save(new DateTimeField(entityId: $ids['entityId'], fieldKey: 'a', value: '2026-05-24T12:00:00+00:00'));
        $b = $repository->save(new DateTimeField(entityId: $ids['entityId'], fieldKey: 'b', value: '2026-05-25T12:00:00+00:00'));

        $repository->delete($b);

        $list = $repository->findAll(10, 0);

        self::assertCount(1, $list);
        self::assertSame('a', $list[0]->fieldKey);
    }
}
