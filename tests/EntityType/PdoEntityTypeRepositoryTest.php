<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityType;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\PdoEntityTypeRepository;
use PHPUnit\Framework\TestCase;

final class PdoEntityTypeRepositoryTest extends TestCase
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
            $projectRoot . '/database/schema/field_defs.sql',
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

    public function testFindByIdReturnsEntityType(): void
    {
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('Book', 'book')");

        $repository = new PdoEntityTypeRepository($this->executor, $this->orgId);
        $entityType = $repository->findById(1);

        self::assertNotNull($entityType);
        self::assertSame(1, $entityType->id);
        self::assertSame('Book', $entityType->name);
        self::assertSame('book', $entityType->slug);
    }

    public function testFindByIdReturnsNullWhenAbsent(): void
    {
        $repository = new PdoEntityTypeRepository($this->executor, $this->orgId);
        self::assertNull($repository->findById(99));
    }

    public function testFindBySlugReturnsEntityType(): void
    {
        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('Doc', 'doc')");

        $repository = new PdoEntityTypeRepository($this->executor, $this->orgId);
        $entityType = $repository->findBySlug('doc');

        self::assertNotNull($entityType);
        self::assertSame('doc', $entityType->slug);
    }

    public function testSaveReturnsNewId(): void
    {
        $repository = new PdoEntityTypeRepository($this->executor, $this->orgId);
        $id = $repository->save(new EntityType(name: 'Note', slug: 'note'));

        self::assertSame(1, $id);
    }

    public function testSavedEntityTypeIsRetrievableById(): void
    {
        $repository = new PdoEntityTypeRepository($this->executor, $this->orgId);
        $id = $repository->save(new EntityType(name: 'Tag', slug: 'tag'));
        $entityType = $repository->findById($id);

        self::assertNotNull($entityType);
        self::assertSame('Tag', $entityType->name);
        self::assertSame('tag', $entityType->slug);
    }

    public function testUpdateChangesFields(): void
    {
        $repository = new PdoEntityTypeRepository($this->executor, $this->orgId);
        $id = $repository->save(new EntityType(name: 'Old', slug: 'old'));

        $repository->update(new EntityType(name: 'New', slug: 'new', id: $id));
        $entityType = $repository->findById($id);

        self::assertNotNull($entityType);
        self::assertSame('New', $entityType->name);
        self::assertSame('new', $entityType->slug);
    }

    public function testDeleteRemovesEntityType(): void
    {
        $repository = new PdoEntityTypeRepository($this->executor, $this->orgId);
        $id = $repository->save(new EntityType(name: 'Gone', slug: 'gone'));
        $repository->delete($id);

        self::assertNull($repository->findById($id));
    }

    public function testFindAllReturnsEntityTypesOrderedById(): void
    {
        $repository = new PdoEntityTypeRepository($this->executor, $this->orgId);
        $repository->save(new EntityType(name: 'First', slug: 'first'));
        $repository->save(new EntityType(name: 'Second', slug: 'second'));

        $list = $repository->findAll(10, 0);

        self::assertCount(2, $list);
        self::assertSame('First', $list[0]->name);
        self::assertSame('Second', $list[1]->name);
    }

    public function testFindAllRespectsLimitAndOffset(): void
    {
        $repository = new PdoEntityTypeRepository($this->executor, $this->orgId);

        for ($i = 1; $i <= 5; $i++) {
            $repository->save(new EntityType(name: "Row {$i}", slug: 't' . $i));
        }

        $list = $repository->findAll(2, 2);

        self::assertCount(2, $list);
        self::assertSame('Row 3', $list[0]->name);
        self::assertSame('Row 4', $list[1]->name);
    }
}
