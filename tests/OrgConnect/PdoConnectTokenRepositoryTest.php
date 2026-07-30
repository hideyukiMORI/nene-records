<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\OrgConnect;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\OrgConnect\ConnectTokenService;
use NeNeRecords\OrgConnect\PdoConnectTokenRepository;
use NeNeRecords\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;

final class PdoConnectTokenRepositoryTest extends TestCase
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
        $this->orgId->set(1);

        $path = dirname(__DIR__, 2) . '/database/schema/org_connect_tokens.sql';
        self::assertFileExists($path);
        $raw = trim((string) file_get_contents($path));

        foreach (preg_split('/;\R/s', $raw) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $this->executor->execute($statement);
            }
        }
    }

    public function testSaveThenReadBackTheEnvelope(): void
    {
        $repository = $this->repository();

        $summary = $repository->save(ConnectTokenService::Contact, 'envelope-one', 'abcd');

        self::assertSame(ConnectTokenService::Contact, $summary->service);
        self::assertSame('abcd', $summary->hint);
        self::assertSame('envelope-one', $repository->findEnvelope(ConnectTokenService::Contact));
    }

    public function testSummaryReadsNeverSelectTheCiphertext(): void
    {
        $repository = $this->repository();
        $repository->save(ConnectTokenService::Contact, 'envelope-one', 'abcd');

        // The summary type has nowhere to put a secret; this asserts the SQL agrees, so a
        // future `SELECT *` cannot quietly start carrying the ciphertext into the HTTP layer.
        $encoded = (string) json_encode([
            $repository->findAllSummaries(),
            $repository->findSummary(ConnectTokenService::Contact),
        ]);

        self::assertStringNotContainsString('envelope-one', $encoded);
    }

    public function testSavingTwiceUpdatesInPlaceAndKeepsCreatedAt(): void
    {
        $repository = $this->repository();

        $first = $repository->save(ConnectTokenService::Contact, 'envelope-one', 'abcd');
        $second = $repository->save(ConnectTokenService::Contact, 'envelope-two', 'wxyz');

        self::assertSame($first->createdAt, $second->createdAt);
        self::assertSame('envelope-two', $repository->findEnvelope(ConnectTokenService::Contact));
        self::assertCount(1, $repository->findAllSummaries());
    }

    public function testAnotherOrganizationCannotSeeOrOverwriteTheToken(): void
    {
        $repository = $this->repository();
        $repository->save(ConnectTokenService::Contact, 'envelope-one', 'abcd');

        $this->orgId->set(2);

        self::assertSame([], $repository->findAllSummaries());
        self::assertNull($repository->findSummary(ConnectTokenService::Contact));
        self::assertNull($repository->findEnvelope(ConnectTokenService::Contact));

        // A second org installing its own token must not touch the first org's row.
        $repository->save(ConnectTokenService::Contact, 'envelope-two', 'wxyz');
        $this->orgId->set(1);

        self::assertSame('envelope-one', $repository->findEnvelope(ConnectTokenService::Contact));
    }

    public function testDeleteIsScopedToTheOrganization(): void
    {
        $repository = $this->repository();
        $repository->save(ConnectTokenService::Contact, 'envelope-one', 'abcd');

        $this->orgId->set(2);
        $repository->save(ConnectTokenService::Contact, 'envelope-two', 'wxyz');
        $repository->delete(ConnectTokenService::Contact);

        self::assertNull($repository->findEnvelope(ConnectTokenService::Contact));

        $this->orgId->set(1);
        self::assertSame('envelope-one', $repository->findEnvelope(ConnectTokenService::Contact));
    }

    public function testRowsWithAnUnknownServiceAreSkippedRatherThanFatal(): void
    {
        $repository = $this->repository();
        $repository->save(ConnectTokenService::Contact, 'envelope-one', 'abcd');

        // Simulates an integration that was removed from the enum, or a hand-edited row:
        // the admin list must still load so the operator can clean up.
        $this->executor->execute(
            'INSERT INTO org_connect_tokens
                 (organization_id, service, token_ciphertext, token_hint, created_at, updated_at)
             VALUES (1, ?, ?, ?, ?, ?)',
            ['retired', 'envelope-old', 'zzzz', '2026-01-01 00:00:00', '2026-01-01 00:00:00'],
        );

        self::assertCount(1, $repository->findAllSummaries());
    }

    private function repository(): PdoConnectTokenRepository
    {
        return new PdoConnectTokenRepository($this->executor, $this->orgId, new FixedClock());
    }
}
