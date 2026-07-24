<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Analytics;

use NeNeRecords\Analytics\RefererHost;
use PHPUnit\Framework\TestCase;

final class RefererHostTest extends TestCase
{
    public function testKeepsHostOnly(): void
    {
        self::assertSame('www.google.com', RefererHost::fromReferer('https://www.google.com/search?q=alice@example.com'));
        self::assertSame('t.co', RefererHost::fromReferer('https://t.co/abc123'));
    }

    public function testLowercasesHost(): void
    {
        self::assertSame('example.com', RefererHost::fromReferer('https://EXAMPLE.com/Path'));
    }

    public function testNullForEmptyOrRelativeOrUnparseable(): void
    {
        self::assertNull(RefererHost::fromReferer(''));
        self::assertNull(RefererHost::fromReferer('   '));
        self::assertNull(RefererHost::fromReferer('/relative/path'));
    }
}
