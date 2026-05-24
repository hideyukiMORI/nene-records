<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityRelation;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeRecords\EntityRelation\PdoEntityRelationRepository;
use PHPUnit\Framework\TestCase;

final class PdoEntityRelationRepositoryTest extends TestCase
{
    private PdoDatabaseQueryExecutor $executor;
    private PdoEntityRelationRepository $repository;

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

        $this->executor->execute("INSERT INTO entity_types (id, name, slug) VALUES (1, 'Article', 'article'), (2, 'Author', 'author')");
        $this->executor->execute('INSERT INTO entities (id, entity_type_id) VALUES (1, 1), (2, 2), (3, 2)');

        $this->repository = new PdoEntityRelationRepository($this->executor);
    }

    public function testAttachListDetachAndReplaceOneCardinality(): void
    {
        self::assertSame([], $this->repository->findByEntityIdAndFieldKey(1, 'author'));

        $this->repository->attach(1, 2, 'author');
        self::assertTrue($this->repository->isAttached(1, 2, 'author'));
        self::assertCount(1, $this->repository->findByEntityIdAndFieldKey(1, 'author'));

        $this->repository->detachAllForFieldKey(1, 'author');
        $this->repository->attach(1, 3, 'author');

        self::assertFalse($this->repository->isAttached(1, 2, 'author'));
        self::assertTrue($this->repository->isAttached(1, 3, 'author'));

        $this->repository->detach(1, 3, 'author');
        self::assertFalse($this->repository->isAttached(1, 3, 'author'));
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
            $projectRoot . '/database/schema/entity_relations.sql',
        ];

        $statements = [];

        foreach ($paths as $path) {
            $sql = file_get_contents($path);

            if ($sql === false) {
                throw new \RuntimeException('Failed to read schema file: ' . $path);
            }

            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                $statements[] = $statement;
            }
        }

        return $statements;
    }
}
