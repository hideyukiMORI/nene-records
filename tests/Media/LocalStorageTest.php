<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Media;

use NeNeRecords\Media\LocalStorage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LocalStorageTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/nene-storage-test-' . bin2hex(random_bytes(4));
        mkdir($this->root, 0755, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->rmdirRecursive($this->root);
    }

    public function testWriteFailureThrowsWithoutEmittingAPhpWarning(): void
    {
        // #949: display_errors な共有ホスティングでは、mkdir/file_put_contents の
        // raw warning が HTTP 本文へ流れて 200/text/html を確定させてしまう。
        // 失敗は RuntimeException だけで表面化しなければならない。
        // 平ファイルでディレクトリの場所を塞ぐ — root 実行でも必ず失敗する。
        file_put_contents($this->root . '/blocked', 'plain file');
        $storage = new LocalStorage($this->root);

        set_error_handler(static function (int $severity, string $message): bool {
            if ((error_reporting() & $severity) !== 0) {
                self::fail('an unsuppressed PHP warning leaked: ' . $message);
            }

            return true;
        });

        try {
            $storage->write('blocked/2026/x.png', 'bytes');
            self::fail('expected RuntimeException');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('Failed to create media directory', $e->getMessage());
        } finally {
            restore_error_handler();
        }
    }

    public function testWriteFromUploadStoresFileUnderKey(): void
    {
        $storage = new LocalStorage($this->root);
        $tmp = $this->makeTempFile('hello world');

        $storage->writeFromUpload('2026/06/abc.txt', $tmp);

        self::assertTrue($storage->exists('2026/06/abc.txt'));
        self::assertFileExists($this->root . '/2026/06/abc.txt');
        self::assertSame(11, $storage->size('2026/06/abc.txt'));
    }

    public function testReadStreamReturnsStoredContents(): void
    {
        $storage = new LocalStorage($this->root);
        $storage->writeFromUpload('2026/06/abc.txt', $this->makeTempFile('payload'));

        $stream = $storage->readStream('2026/06/abc.txt');
        $contents = stream_get_contents($stream);
        fclose($stream);

        self::assertSame('payload', $contents);
    }

    public function testDeleteRemovesObjectAndIsBestEffort(): void
    {
        $storage = new LocalStorage($this->root);
        $storage->writeFromUpload('2026/06/abc.txt', $this->makeTempFile('x'));

        $storage->delete('2026/06/abc.txt');
        self::assertFalse($storage->exists('2026/06/abc.txt'));

        // Deleting a missing object must not raise.
        $storage->delete('2026/06/missing.txt');
        self::assertFalse($storage->exists('2026/06/missing.txt'));
    }

    public function testPublicUrlAndKeyFromUrlAreInverses(): void
    {
        $storage = new LocalStorage($this->root);

        self::assertSame('/media/2026/06/abc.png', $storage->publicUrl('2026/06/abc.png'));
        self::assertSame('2026/06/abc.png', $storage->keyFromUrl('/media/2026/06/abc.png'));
        self::assertSame('2026/06/abc.png', $storage->keyFromUrl($storage->publicUrl('2026/06/abc.png')));
    }

    public function testRejectsTraversalKeys(): void
    {
        $storage = new LocalStorage($this->root);

        $this->expectException(RuntimeException::class);
        $storage->writeFromUpload('../escape.txt', $this->makeTempFile('x'));
    }

    private function makeTempFile(string $contents): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'nene_src_');
        self::assertIsString($tmp);
        file_put_contents($tmp, $contents);

        return $tmp;
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $entries = scandir($dir);

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
        }

        rmdir($dir);
    }
}
