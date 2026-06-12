<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Layout;

use NeNeRecords\Layout\PublicLayouts;
use PHPUnit\Framework\TestCase;

final class PublicLayoutsTest extends TestCase
{
    public function testKnownLayoutsAreValid(): void
    {
        self::assertTrue(PublicLayouts::isValid('standard'));
        self::assertTrue(PublicLayouts::isValid('full'));
        self::assertTrue(PublicLayouts::isValid('bare'));
    }

    public function testUnknownLayoutIsInvalid(): void
    {
        self::assertFalse(PublicLayouts::isValid('fancy'));
        self::assertFalse(PublicLayouts::isValid(''));
    }

    public function testResolvePrefersEntityOverride(): void
    {
        self::assertSame('bare', PublicLayouts::resolve('bare', 'full'));
    }

    public function testResolveFallsBackToTypeDefault(): void
    {
        self::assertSame('full', PublicLayouts::resolve(null, 'full'));
    }

    public function testResolveFallsBackToGlobalDefaultWhenBothMissing(): void
    {
        self::assertSame('standard', PublicLayouts::resolve(null, null));
    }

    public function testResolveIgnoresInvalidValues(): void
    {
        // An invalid entity layout falls through to the type default; an invalid
        // type default falls through to the global default.
        self::assertSame('full', PublicLayouts::resolve('bogus', 'full'));
        self::assertSame('standard', PublicLayouts::resolve('bogus', 'nope'));
    }

    public function testColumnLayoutsAreValid(): void
    {
        self::assertTrue(PublicLayouts::isValid('two-col'));
        self::assertTrue(PublicLayouts::isValid('three-col'));
    }

    public function testRegionsPerLayout(): void
    {
        self::assertSame(['main'], PublicLayouts::regions('standard'));
        self::assertSame(['main', 'sidebar'], PublicLayouts::regions('two-col'));
        self::assertSame(['main', 'sidebar', 'aside'], PublicLayouts::regions('three-col'));
        // Unknown layout falls back to the default layout's regions.
        self::assertSame(['main'], PublicLayouts::regions('bogus'));
    }
}
