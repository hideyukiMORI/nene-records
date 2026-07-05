<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Database\Preflight;

use NeNeRecords\Database\Preflight\MigrationVersions;
use PHPUnit\Framework\TestCase;

final class MigrationVersionsTest extends TestCase
{
    public function testReturnsSortedVersionPrefixesFromDirectory(): void
    {
        $dir = $this->tempDir();
        $files = [
            '20260716000000_stamp_app_identity_marker.php',
            '20260524000000_initial.php',
            '20260601000000_add_thing.php',
            'not_a_migration.php',    // no numeric prefix → ignored
            '20260602000000_readme.txt', // not a .php → ignored
        ];
        foreach ($files as $name) {
            touch($dir . '/' . $name);
        }

        self::assertSame(
            ['20260524000000', '20260601000000', '20260716000000'],
            MigrationVersions::known($dir),
        );

        foreach ($files as $name) {
            unlink($dir . '/' . $name);
        }
        rmdir($dir);
    }

    public function testReturnsEmptyWhenDirectoryMissing(): void
    {
        self::assertSame([], MigrationVersions::known('/no/such/migrations'));
    }

    public function testDefaultReadsShippedMigrations(): void
    {
        $versions = MigrationVersions::known();

        self::assertNotEmpty($versions);
        foreach ($versions as $version) {
            self::assertMatchesRegularExpression('/^\d+$/', $version);
        }
        // Ships the identity-marker migration this feature adds.
        self::assertContains('20260716000000', $versions);
    }

    private function tempDir(): string
    {
        $dir = sys_get_temp_dir() . '/nene-migrations-' . uniqid('', true);
        mkdir($dir);

        return $dir;
    }
}
