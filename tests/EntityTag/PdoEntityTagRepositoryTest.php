<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityTag;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeRecords\EntityTag\PdoEntityTagRepository;
use PHPUnit\Framework\TestCase;

final class PdoEntityTagRepositoryTest extends TestCase
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

        $this->executor->execute("INSERT INTO entity_types (name, slug) VALUES ('Base', 'base')");
        $this->executor->execute('INSERT INTO entities (entity_type_id) VALUES (1)');
        $this->executor->execute("INSERT INTO tags (slug, name) VALUES ('featured', 'Featured')");
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

    public function testAttachFindAndDetach(): void
    {
        $repository = new PdoEntityTagRepository($this->executor);

        self::assertFalse($repository->isAttached(1, 1));

        $repository->attach(1, 1);

        self::assertTrue($repository->isAttached(1, 1));

        $tags = $repository->findTagsByEntityId(1);

        self::assertCount(1, $tags);
        self::assertSame('featured', $tags[0]->slug);

        $repository->detach(1, 1);

        self::assertFalse($repository->isAttached(1, 1));
        self::assertSame([], $repository->findTagsByEntityId(1));
    }
}
