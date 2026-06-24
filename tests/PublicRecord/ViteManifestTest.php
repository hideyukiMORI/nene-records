<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\ViteManifest;
use PHPUnit\Framework\TestCase;

final class ViteManifestTest extends TestCase
{
    private string $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manifest = __DIR__ . '/fixtures/spa-build/frontend/dist/.vite/manifest.json';
    }

    public function testResolvesEntryJsCssAndPreloadFromImportGraph(): void
    {
        $entry = ViteManifest::resolveEntry($this->manifest);

        self::assertNotNull($entry);
        self::assertSame('/assets/index-ABC.js', $entry['js']);
        // CSS comes from the entry AND its imported chunks.
        self::assertContains('/assets/index-ABC.css', $entry['css']);
        self::assertContains('/assets/vendor-XYZ.css', $entry['css']);
        // Imported chunk files become module preloads.
        self::assertContains('/assets/vendor-XYZ.js', $entry['preload']);
    }

    public function testReturnsNullWhenManifestMissing(): void
    {
        self::assertNull(ViteManifest::resolveEntry(__DIR__ . '/fixtures/does-not-exist.json'));
    }

    public function testRespectsCustomBasePath(): void
    {
        $entry = ViteManifest::resolveEntry($this->manifest, '/app/');

        self::assertNotNull($entry);
        self::assertSame('/app/assets/index-ABC.js', $entry['js']);
        self::assertContains('/app/assets/vendor-XYZ.css', $entry['css']);
    }
}
