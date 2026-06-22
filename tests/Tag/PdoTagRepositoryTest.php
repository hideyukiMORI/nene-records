<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Tag;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\DatabaseConstraintException;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Tag\PdoTagRepository;
use NeNeRecords\Tag\Tag;
use PHPUnit\Framework\TestCase;

final class PdoTagRepositoryTest extends TestCase
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
        $repository = new PdoTagRepository($this->executor, $this->orgId);
        $id = $repository->save(new Tag(slug: 'featured', name: 'Featured'));

        $tag = $repository->findBySlug('featured');

        self::assertNotNull($tag);
        self::assertSame($id, $tag->id);
        self::assertSame('Featured', $tag->name);
    }

    public function testFindAllReturnsTagsInIdOrder(): void
    {
        $repository = new PdoTagRepository($this->executor, $this->orgId);
        $repository->save(new Tag(slug: 'a', name: 'A'));
        $repository->save(new Tag(slug: 'b', name: 'B'));

        $tags = $repository->findAll(10, 0);

        self::assertCount(2, $tags);
        self::assertSame('a', $tags[0]->slug);
        self::assertSame('b', $tags[1]->slug);
    }

    public function testSameSlugIsAllowedAcrossOrganizations(): void
    {
        $orgOne = new RequestScopedHolder();
        $orgOne->set(1);
        $orgTwo = new RequestScopedHolder();
        $orgTwo->set(2);

        $repositoryOne = new PdoTagRepository($this->executor, $orgOne);
        $repositoryTwo = new PdoTagRepository($this->executor, $orgTwo);

        $idOne = $repositoryOne->save(new Tag(slug: 'shared', name: 'Shared (org 1)'));
        $idTwo = $repositoryTwo->save(new Tag(slug: 'shared', name: 'Shared (org 2)'));

        self::assertNotSame($idOne, $idTwo);
        self::assertSame('Shared (org 1)', $repositoryOne->findBySlug('shared')?->name);
        self::assertSame('Shared (org 2)', $repositoryTwo->findBySlug('shared')?->name);
    }

    public function testDuplicateSlugWithinOrganizationIsRejected(): void
    {
        $repository = new PdoTagRepository($this->executor, $this->orgId);
        $repository->save(new Tag(slug: 'dup', name: 'First'));

        $this->expectException(DatabaseConstraintException::class);
        $repository->save(new Tag(slug: 'dup', name: 'Second'));
    }
}
