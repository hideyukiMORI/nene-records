<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\FieldDef;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\PdoFieldDefRepository;
use PHPUnit\Framework\TestCase;

final class PdoFieldDefRepositoryTest extends TestCase
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
            $projectRoot . '/database/schema/field_defs.sql',
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

    public function testFindByIdReturnsFieldDef(): void
    {
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('Article', 'article')");
        $this->executor->execute("INSERT INTO field_defs (entity_type_id, field_key, data_type) VALUES (1, 'title', 'text')");

        $repository = new PdoFieldDefRepository($this->executor);
        $fieldDef = $repository->findById(1);

        self::assertNotNull($fieldDef);
        self::assertSame(1, $fieldDef->id);
        self::assertSame(1, $fieldDef->entityTypeId);
        self::assertSame('title', $fieldDef->fieldKey);
        self::assertSame('text', $fieldDef->dataType);
    }

    public function testFindByEntityTypeIdAndFieldKeyReturnsActiveDefinition(): void
    {
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('Article', 'article')");
        $this->executor->execute("INSERT INTO field_defs (entity_type_id, field_key, data_type) VALUES (1, 'title', 'text')");

        $repository = new PdoFieldDefRepository($this->executor);
        $fieldDef = $repository->findByEntityTypeIdAndFieldKey(1, 'title');

        self::assertNotNull($fieldDef);
        self::assertSame('title', $fieldDef->fieldKey);
    }

    public function testSoftDeleteHidesFromFindById(): void
    {
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('Article', 'article')");
        $this->executor->execute("INSERT INTO field_defs (entity_type_id, field_key, data_type) VALUES (1, 'title', 'text')");

        $repository = new PdoFieldDefRepository($this->executor);
        $repository->softDelete(1);

        self::assertNull($repository->findById(1));
        self::assertNull($repository->findByEntityTypeIdAndFieldKey(1, 'title'));
    }

    public function testFindAllFiltersByEntityTypeId(): void
    {
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('A', 'a')");
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('B', 'b')");
        $this->executor->execute("INSERT INTO field_defs (entity_type_id, field_key, data_type) VALUES (1, 'title', 'text')");
        $this->executor->execute("INSERT INTO field_defs (entity_type_id, field_key, data_type) VALUES (2, 'body', 'text')");

        $repository = new PdoFieldDefRepository($this->executor);
        $list = $repository->findAll(1, 10, 0);

        self::assertCount(1, $list);
        self::assertSame('title', $list[0]->fieldKey);
    }

    public function testSaveReturnsNewId(): void
    {
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('Article', 'article')");

        $repository = new PdoFieldDefRepository($this->executor);
        $id = $repository->save(new FieldDef(entityTypeId: 1, fieldKey: 'summary', dataType: 'text'));

        self::assertSame(1, $id);
    }

    public function testUpdateChangesFields(): void
    {
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('Article', 'article')");
        $this->executor->execute("INSERT INTO field_defs (entity_type_id, field_key, data_type) VALUES (1, 'title', 'text')");

        $repository = new PdoFieldDefRepository($this->executor);
        $repository->update(new FieldDef(entityTypeId: 1, fieldKey: 'headline', dataType: 'text', id: 1));
        $fieldDef = $repository->findById(1);

        self::assertNotNull($fieldDef);
        self::assertSame('headline', $fieldDef->fieldKey);
    }
}
