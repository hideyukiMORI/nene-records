<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization;

use NeNeRecords\Organization\CreateOrganizationInput;
use NeNeRecords\Organization\CreateOrganizationUseCase;
use NeNeRecords\Organization\DeleteOrganizationInput;
use NeNeRecords\Organization\DeleteOrganizationUseCase;
use NeNeRecords\Organization\OrgMediaInventory;
use NeNeRecords\Organization\OrgMediaInventoryReaderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * #1018: deleting an org strands its media files on disk. We do not delete them —
 * we write down what was stranded, because that is the last moment anyone can tell
 * which org the files belonged to (storage keys carry no tenant).
 */
final class OrgMediaInventoryTest extends TestCase
{
    public function testInventorySumsOnlyFilesThatArePresent(): void
    {
        $inventory = OrgMediaInventory::fromEntries(7, [
            ['key' => '2026/07/aaa.png', 'bytes' => 100, 'present' => true],
            ['key' => '2026/07/bbb.jpg', 'bytes' => 250, 'present' => true],
            // A row whose file is already gone must not inflate the byte total, but
            // must still be visible as a count — a non-zero value means the DB and
            // the disk have drifted, which is worth seeing.
            ['key' => '2026/07/ccc.pdf', 'bytes' => 0, 'present' => false],
        ]);

        self::assertSame(2, $inventory->fileCount);
        self::assertSame(350, $inventory->totalBytes);
        self::assertSame(1, $inventory->missingCount);
        self::assertFalse($inventory->isEmpty());
    }

    public function testLogContextCapsKeysSoOneTenantCannotProduceAnUnboundedLine(): void
    {
        $entries = [];

        for ($i = 0; $i < 250; ++$i) {
            $entries[] = ['key' => sprintf('2026/07/%03d.png', $i), 'bytes' => 1, 'present' => true];
        }

        $context = OrgMediaInventory::fromEntries(1, $entries)->toLogContext(maxKeys: 200);

        self::assertCount(200, $context['keys']);
        self::assertTrue($context['keys_truncated']);
        // The counts stay exact even when the key list is cut.
        self::assertSame(250, $context['file_count']);
        self::assertSame(250, $context['total_bytes']);
    }

    public function testDeleteLogsTheStrandedFilesBeforeRemovingTheOrg(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $created = (new CreateOrganizationUseCase(
            $organizations,
            new RecordingDefaultContentTypeSeeder(),
            new RecordingDefaultSettingDefsSeeder(),
        ))->execute(new CreateOrganizationInput(name: 'Doomed', slug: 'doomed'));

        $logger = new class () extends AbstractLogger {
            /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
            public array $records = [];

            /**
             * @param mixed                $level
             * @param string|\Stringable   $message
             * @param array<string, mixed> $context
             */
            public function log($level, $message, array $context = []): void
            {
                $this->records[] = ['level' => (string) $level, 'message' => (string) $message, 'context' => $context];
            }
        };

        $reader = new class () implements OrgMediaInventoryReaderInterface {
            public ?int $askedFor = null;

            public function forOrganization(int $organizationId): OrgMediaInventory
            {
                $this->askedFor = $organizationId;

                return OrgMediaInventory::fromEntries($organizationId, [
                    ['key' => '2026/07/kept.png', 'bytes' => 4096, 'present' => true],
                ]);
            }
        };

        (new DeleteOrganizationUseCase($organizations, $reader, $logger))
            ->execute(new DeleteOrganizationInput(id: $created->id));

        self::assertSame($created->id, $reader->askedFor, 'the inventory must be taken for the org being deleted');
        self::assertNull($organizations->findById($created->id), 'the delete itself must still happen');

        self::assertCount(1, $logger->records);
        self::assertSame('info', $logger->records[0]['level']);
        self::assertSame(1, $logger->records[0]['context']['file_count']);
        self::assertSame(4096, $logger->records[0]['context']['total_bytes']);
        self::assertContains('2026/07/kept.png', $logger->records[0]['context']['keys']);
    }

    /**
     * The ordering IS the feature. Once the org is deleted its `media` rows are
     * purged, and a storage key has no tenant in it — so an inventory taken
     * afterwards can no longer attribute anything. Asserting only "it was logged"
     * passes even when the call is moved below the delete, which is why this
     * observes whether the org still existed at the moment the reader ran.
     */
    public function testInventoryIsTakenWhileTheOrgStillExists(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $created = (new CreateOrganizationUseCase(
            $organizations,
            new RecordingDefaultContentTypeSeeder(),
            new RecordingDefaultSettingDefsSeeder(),
        ))->execute(new CreateOrganizationInput(name: 'Doomed', slug: 'doomed-3'));

        $reader = new class ($organizations) implements OrgMediaInventoryReaderInterface {
            public ?bool $orgVisibleWhenRead = null;

            public function __construct(private InMemoryOrganizationRepository $organizations)
            {
            }

            public function forOrganization(int $organizationId): OrgMediaInventory
            {
                $this->orgVisibleWhenRead = $this->organizations->findById($organizationId) !== null;

                return OrgMediaInventory::fromEntries($organizationId, [
                    ['key' => '2026/07/kept.png', 'bytes' => 1, 'present' => true],
                ]);
            }
        };

        (new DeleteOrganizationUseCase($organizations, $reader, new NullLogger()))
            ->execute(new DeleteOrganizationInput(id: $created->id));

        self::assertTrue(
            $reader->orgVisibleWhenRead,
            'the inventory must run BEFORE the delete — afterwards the media rows are gone and nothing can be attributed',
        );
    }

    public function testAnOrgWithNoMediaLogsNothing(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $created = (new CreateOrganizationUseCase(
            $organizations,
            new RecordingDefaultContentTypeSeeder(),
            new RecordingDefaultSettingDefsSeeder(),
        ))->execute(new CreateOrganizationInput(name: 'Empty', slug: 'empty'));

        $logger = new class () extends AbstractLogger {
            /** @var list<string> */
            public array $messages = [];

            /**
             * @param mixed                $level
             * @param string|\Stringable   $message
             * @param array<string, mixed> $context
             */
            public function log($level, $message, array $context = []): void
            {
                $this->messages[] = (string) $message;
            }
        };

        $reader = new class () implements OrgMediaInventoryReaderInterface {
            public function forOrganization(int $organizationId): OrgMediaInventory
            {
                return OrgMediaInventory::fromEntries($organizationId, []);
            }
        };

        (new DeleteOrganizationUseCase($organizations, $reader, $logger))
            ->execute(new DeleteOrganizationInput(id: $created->id));

        self::assertSame([], $logger->messages, 'nothing stranded means nothing to say');
    }

    /**
     * The inventory is a record, not a precondition. An operator who asked for a
     * delete must get it even if the bookkeeping fails.
     */
    public function testDeleteStillSucceedsWhenTheInventoryThrows(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $created = (new CreateOrganizationUseCase(
            $organizations,
            new RecordingDefaultContentTypeSeeder(),
            new RecordingDefaultSettingDefsSeeder(),
        ))->execute(new CreateOrganizationInput(name: 'Doomed', slug: 'doomed-2'));

        $logger = new class () extends AbstractLogger {
            /** @var list<string> */
            public array $levels = [];

            /**
             * @param mixed                $level
             * @param string|\Stringable   $message
             * @param array<string, mixed> $context
             */
            public function log($level, $message, array $context = []): void
            {
                $this->levels[] = (string) $level;
            }
        };

        $reader = new class () implements OrgMediaInventoryReaderInterface {
            public function forOrganization(int $organizationId): OrgMediaInventory
            {
                throw new RuntimeException('storage unavailable');
            }
        };

        (new DeleteOrganizationUseCase($organizations, $reader, $logger))
            ->execute(new DeleteOrganizationInput(id: $created->id));

        self::assertNull($organizations->findById($created->id));
        self::assertSame(['warning'], $logger->levels, 'the failure must be visible, not swallowed');
    }
}
