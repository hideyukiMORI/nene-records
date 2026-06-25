<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\PublicRecord\PublicLocale;
use PHPUnit\Framework\TestCase;

final class PublicLocaleTest extends TestCase
{
    public function testResolvesSupportedLocales(): void
    {
        self::assertSame('de', PublicLocale::resolve('de'));
        self::assertSame('zh-Hans', PublicLocale::resolve('zh-Hans'));
        self::assertSame('pt-BR', PublicLocale::resolve('pt-BR'));
    }

    public function testTrimsWhitespace(): void
    {
        self::assertSame('ja', PublicLocale::resolve('  ja  '));
    }

    public function testRejectsUnsupportedOrAbsent(): void
    {
        self::assertNull(PublicLocale::resolve(null));
        self::assertNull(PublicLocale::resolve(''));
        self::assertNull(PublicLocale::resolve('xx'));
        self::assertNull(PublicLocale::resolve('en-US'));
    }
}
