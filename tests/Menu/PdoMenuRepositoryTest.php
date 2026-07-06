<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Menu;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Http\RequestScopedHolder;
use Nene2\Http\UtcClock;
use NeNeRecords\Menu\Menu;
use NeNeRecords\Menu\PdoMenuRepository;
use PHPUnit\Framework\TestCase;

final class PdoMenuRepositoryTest extends TestCase
{
    private PdoDatabaseQueryExecutor $executor;

    /** @var RequestScopedHolder<int> */
    private RequestScopedHolder $orgId;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = new PdoConnectionFactory(new DatabaseConfig(
            null,
            'test',
            'sqlite',
            'localhost',
            1,
            ':memory:',
            'nene-records-test',
            '',
            'utf8',
        ));

        $this->executor = new PdoDatabaseQueryExecutor($factory);

        $this->orgId = new RequestScopedHolder();
        $this->orgId->set(0);

        $path = dirname(__DIR__, 2) . '/database/schema/menus.sql';
        self::assertFileExists($path);
        $raw = trim((string) file_get_contents($path));

        foreach (preg_split('/;\R/s', $raw) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $this->executor->execute($statement);
            }
        }
    }

    public function testSaveAndFindById(): void
    {
        $repository = new PdoMenuRepository($this->executor, $this->orgId, new UtcClock());
        $id = $repository->save(new Menu(null, 'Main nav', 'main-nav', 'header', '', ''));

        $found = $repository->findById($id);
        self::assertNotNull($found);
        self::assertSame('Main nav', $found->name);
        self::assertSame('main-nav', $found->slug);
        self::assertSame('header', $found->location);
    }

    public function testFindByIdSupportsNullLocation(): void
    {
        $repository = new PdoMenuRepository($this->executor, $this->orgId, new UtcClock());
        $id = $repository->save(new Menu(null, 'Categories', 'categories', null, '', ''));

        $found = $repository->findById($id);
        self::assertNotNull($found);
        self::assertNull($found->location);
    }

    public function testExistsBySlug(): void
    {
        $repository = new PdoMenuRepository($this->executor, $this->orgId, new UtcClock());
        $id = $repository->save(new Menu(null, 'Footer', 'footer', 'footer', '', ''));

        self::assertTrue($repository->existsBySlug('footer'));
        self::assertFalse($repository->existsBySlug('footer', $id));
        self::assertFalse($repository->existsBySlug('missing'));
    }

    public function testUpdateChangesFields(): void
    {
        $repository = new PdoMenuRepository($this->executor, $this->orgId, new UtcClock());
        $id = $repository->save(new Menu(null, 'Old', 'old', null, '', ''));
        $existing = $repository->findById($id);
        self::assertNotNull($existing);

        $repository->update(new Menu($id, 'New', 'new', 'footer', $existing->createdAt, ''));

        $updated = $repository->findById($id);
        self::assertNotNull($updated);
        self::assertSame('New', $updated->name);
        self::assertSame('footer', $updated->location);
    }

    public function testDeleteRemovesMenu(): void
    {
        $repository = new PdoMenuRepository($this->executor, $this->orgId, new UtcClock());
        $id = $repository->save(new Menu(null, 'Temp', 'temp', null, '', ''));

        $repository->delete($id);

        self::assertNull($repository->findById($id));
        self::assertSame([], $repository->findAll());
    }
}
