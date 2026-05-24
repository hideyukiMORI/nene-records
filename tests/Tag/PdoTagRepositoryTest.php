<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Tag;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeRecords\Tag\PdoTagRepository;
use NeNeRecords\Tag\Tag;
use PHPUnit\Framework\TestCase;

final class PdoTagRepositoryTest extends TestCase
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

        foreach ($this->schemaStatements() as $statement) {
            $this->executor->execute($statement);
        }
    }

    /**
     * @return list<string>
     */
    private function schemaStatements(): array
    {
        $path = dirname(__DIR__, 2) . '/database/schema/tags.sql';
        self::assertFileExists($path);
        $raw = trim((string) file_get_contents($path));
        $statements = [];

        foreach (preg_split('/;\R/s', $raw) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $statements[] = $statement;
            }
        }

        return $statements;
    }

    public function testSaveAndFindBySlug(): void
    {
        $repository = new PdoTagRepository($this->executor);
        $id = $repository->save(new Tag(slug: 'featured', name: 'Featured'));

        $tag = $repository->findBySlug('featured');

        self::assertNotNull($tag);
        self::assertSame($id, $tag->id);
        self::assertSame('Featured', $tag->name);
    }

    public function testFindAllReturnsTagsInIdOrder(): void
    {
        $repository = new PdoTagRepository($this->executor);
        $repository->save(new Tag(slug: 'a', name: 'A'));
        $repository->save(new Tag(slug: 'b', name: 'B'));

        $tags = $repository->findAll(10, 0);

        self::assertCount(2, $tags);
        self::assertSame('a', $tags[0]->slug);
        self::assertSame('b', $tags[1]->slug);
    }
}
