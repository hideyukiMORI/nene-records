<?php

declare(strict_types=1);

namespace NeNeRecords\Tests;

use NeNeRecords\Version;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testReadsTrimmedVersionFromFile(): void
    {
        $file = $this->tempFile("  1.2.3\n");

        self::assertSame('1.2.3', Version::current($file));

        unlink($file);
    }

    public function testReturnsNullWhenFileMissing(): void
    {
        self::assertNull(Version::current('/no/such/VERSION'));
    }

    public function testReturnsNullWhenFileBlank(): void
    {
        $file = $this->tempFile("  \n\t ");

        self::assertNull(Version::current($file));

        unlink($file);
    }

    public function testDefaultReadsCommittedRootVersionAsSemver(): void
    {
        $version = Version::current();

        self::assertNotNull($version);
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);

        // Stays in lockstep with the committed VERSION file (release-agnostic).
        $committed = file_get_contents(__DIR__ . '/../VERSION');
        self::assertNotFalse($committed);
        self::assertSame(trim($committed), $version);
    }

    private function tempFile(string $contents): string
    {
        $file = tempnam(sys_get_temp_dir(), 'nene-version-');
        self::assertNotFalse($file);
        file_put_contents($file, $contents);

        return $file;
    }
}
