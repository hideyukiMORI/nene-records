<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\NavigationItem;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\NavigationItem\NavigationItem;
use NeNeRecords\NavigationItem\PdoNavigationItemRepository;
use PHPUnit\Framework\TestCase;

final class PdoNavigationItemRepositoryTest extends TestCase
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

        foreach ($this->schemaStatements() as $statement) {
            $this->executor->execute($statement);
        }
    }

    /** @return list<string> */
    private function schemaStatements(): array
    {
        $path = dirname(__DIR__, 2) . '/database/schema/navigation_items.sql';
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

    public function testFindAllReturnsEmptyInitially(): void
    {
        $repository = new PdoNavigationItemRepository($this->executor, $this->orgId);
        self::assertSame([], $repository->findAll());
    }

    public function testSaveAndFindById(): void
    {
        $repository = new PdoNavigationItemRepository($this->executor, $this->orgId);
        $item = new NavigationItem(
            id: null,
            label: 'Home',
            url: '/',
            location: 'header',
            displayOrder: 0,
            createdAt: '',
            updatedAt: '',
        );

        $id = $repository->save($item);
        self::assertGreaterThan(0, $id);

        $found = $repository->findById($id);
        self::assertNotNull($found);
        self::assertSame($id, $found->id);
        self::assertSame('Home', $found->label);
        self::assertSame('/', $found->url);
        self::assertSame('header', $found->location);
        self::assertSame(0, $found->displayOrder);
    }

    public function testFindAllReturnsSortedByDisplayOrder(): void
    {
        $repository = new PdoNavigationItemRepository($this->executor, $this->orgId);

        foreach ([
            ['About', '/about', 2],
            ['Home', '/', 0],
            ['Contact', '/contact', 1],
        ] as [$label, $url, $order]) {
            $repository->save(new NavigationItem(null, $label, $url, 'header', $order, '', ''));
        }

        $items = $repository->findAll();
        self::assertCount(3, $items);
        self::assertSame(['Home', 'Contact', 'About'], array_column($items, 'label'));
    }

    public function testUpdateChangesFields(): void
    {
        $repository = new PdoNavigationItemRepository($this->executor, $this->orgId);
        $id = $repository->save(new NavigationItem(null, 'Home', '/', 'header', 0, '', ''));

        $found = $repository->findById($id);
        self::assertNotNull($found);

        $repository->update(new NavigationItem(
            id: $id,
            label: 'Home Updated',
            url: '/home',
            location: 'footer',
            displayOrder: 5,
            createdAt: $found->createdAt,
            updatedAt: '',
        ));

        $updated = $repository->findById($id);
        self::assertNotNull($updated);
        self::assertSame('Home Updated', $updated->label);
        self::assertSame('/home', $updated->url);
        self::assertSame('footer', $updated->location);
        self::assertSame(5, $updated->displayOrder);
    }

    public function testDeleteRemovesItem(): void
    {
        $repository = new PdoNavigationItemRepository($this->executor, $this->orgId);
        $id = $repository->save(new NavigationItem(null, 'To Delete', '/delete', 'header', 0, '', ''));

        $repository->delete($id);

        self::assertNull($repository->findById($id));
        self::assertSame([], $repository->findAll());
    }

    public function testFindByIdReturnsNullForMissing(): void
    {
        $repository = new PdoNavigationItemRepository($this->executor, $this->orgId);
        self::assertNull($repository->findById(9999));
    }
}
