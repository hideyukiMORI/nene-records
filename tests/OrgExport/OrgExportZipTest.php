<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\OrgExport;

use NeNeRecords\OrgExport\OrgExportZip;
use PHPUnit\Framework\TestCase;

/**
 * Media transport archive round-trip (#798): originals are bundled at their
 * storage_key path and restored byte-for-byte on the target, missing/unsafe keys
 * are reported not fatal, and the DB payload survives the trip.
 */
final class OrgExportZipTest extends TestCase
{
    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        $base = tempnam(sys_get_temp_dir(), 'nene-zip-') ?: throw new \RuntimeException('tempnam failed');
        @unlink($base);
        @mkdir($base, 0755, true);
        $this->workDir = $base;
    }

    protected function tearDown(): void
    {
        self::rrmdir($this->workDir);
        parent::tearDown();
    }

    public function testCreateThenOpenRestoresOriginalsAndPayload(): void
    {
        $srcRoot = $this->workDir . '/src-media';
        $this->writeFile($srcRoot . '/2026/06/logo.png', 'PNG-BYTES-1');
        $this->writeFile($srcRoot . '/2026/06/hero.jpg', 'JPG-BYTES-2');

        $payload = [
            'meta'  => ['organization_id' => 5, 'exported_at' => '2026-07-14T00:00:00+00:00'],
            'media' => [
                ['id' => 1, 'storage_key' => '2026/06/logo.png'],
                ['id' => 2, 'storage_key' => '2026/06/hero.jpg'],
                // referenced in the DB but absent on disk → reported missing, not fatal.
                ['id' => 3, 'storage_key' => '2026/06/gone.gif'],
            ],
        ];

        $zipPath = $this->workDir . '/export.zip';
        $created = OrgExportZip::create($zipPath, $payload, $srcRoot);

        self::assertSame(2, $created['added']);
        self::assertSame(['2026/06/gone.gif'], $created['missing']);
        self::assertFileExists($zipPath);

        // Extract into a fresh media root, as the target instance would.
        $destRoot = $this->workDir . '/dest-media';
        $opened   = OrgExportZip::open($zipPath, $destRoot);

        self::assertSame(5, $opened['payload']['meta']['organization_id']);
        self::assertSame(2, $opened['placed']);
        self::assertSame(['2026/06/gone.gif'], $opened['missing']);

        // Originals restored byte-for-byte at their storage_key path.
        self::assertSame('PNG-BYTES-1', file_get_contents($destRoot . '/2026/06/logo.png'));
        self::assertSame('JPG-BYTES-2', file_get_contents($destRoot . '/2026/06/hero.jpg'));
        self::assertFileDoesNotExist($destRoot . '/2026/06/gone.gif');
    }

    public function testUnsafeStorageKeyIsSkipped(): void
    {
        $srcRoot = $this->workDir . '/src2';
        $this->writeFile($srcRoot . '/ok.png', 'OK');

        $payload = [
            'meta'  => ['organization_id' => 1],
            'media' => [
                ['id' => 1, 'storage_key' => '../escape.png'],
                ['id' => 2, 'storage_key' => 'ok.png'],
            ],
        ];

        $zipPath = $this->workDir . '/unsafe.zip';
        $created = OrgExportZip::create($zipPath, $payload, $srcRoot);

        // The traversal key is treated as missing (never resolved under the root).
        self::assertContains('../escape.png', $created['missing']);
        self::assertSame(1, $created['added']);
    }

    public function testOpenRejectsNonExportZip(): void
    {
        $zipPath = $this->workDir . '/plain.zip';
        $zip     = new \ZipArchive();
        self::assertTrue($zip->open($zipPath, \ZipArchive::CREATE) === true);
        $zip->addFromString('readme.txt', 'not an export');
        $zip->close();

        $this->expectException(\RuntimeException::class);
        OrgExportZip::open($zipPath, $this->workDir . '/out');
    }

    private function writeFile(string $path, string $contents): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, $contents);
    }

    private static function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? self::rrmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
