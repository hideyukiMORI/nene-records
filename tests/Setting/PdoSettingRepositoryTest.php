<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Setting;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Http\RequestScopedHolder;
use Nene2\Http\UtcClock;
use NeNeRecords\Setting\PdoSettingRepository;
use NeNeRecords\Setting\SettingRevisionAction;
use PHPUnit\Framework\TestCase;

final class PdoSettingRepositoryTest extends TestCase
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

        $now = date('Y-m-d H:i:s');
        $this->executor->execute(
            'INSERT INTO setting_defs (setting_key, data_type, default_value, is_public, label, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            ['site_name', 'text', 'NeNe Records', 1, 'Site name', $now, $now],
        );
    }

    /** @return list<string> */
    private function schemaStatements(): array
    {
        $path = dirname(__DIR__, 2) . '/database/schema/settings.sql';
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

    public function testApplyValueCreatesRevisionAndStoredValue(): void
    {
        $repository = new PdoSettingRepository($this->executor, $this->orgId, new UtcClock());
        $stored = $repository->applyValue('site_name', 'Updated Site', null);

        self::assertSame('Updated Site', $stored->value);

        $revisions = $repository->findRevisionsByKey('site_name', 10, 0);
        self::assertCount(1, $revisions);
        self::assertSame(SettingRevisionAction::Created, $revisions[0]->action);
    }
}
