<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Analytics;

use NeNeRecords\Analytics\VisitorHasher;
use PHPUnit\Framework\TestCase;

final class VisitorHasherTest extends TestCase
{
    public function testMatchesTheDocumentedRecipe(): void
    {
        $salt = str_repeat("\x01", 32);
        $expected = hash('sha256', $salt . '203.0.113.5' . ':' . '1');

        self::assertSame($expected, VisitorHasher::hash($salt, '203.0.113.5', 1));
        self::assertSame(64, strlen(VisitorHasher::hash($salt, '203.0.113.5', 1)));
    }

    public function testSameVisitorIsStableWithinASalt(): void
    {
        $salt = random_bytes(32);

        self::assertSame(
            VisitorHasher::hash($salt, '203.0.113.5', 1),
            VisitorHasher::hash($salt, '203.0.113.5', 1),
        );
    }

    public function testDifferentOrgYieldsDifferentHashForSameIp(): void
    {
        $salt = random_bytes(32);

        self::assertNotSame(
            VisitorHasher::hash($salt, '203.0.113.5', 1),
            VisitorHasher::hash($salt, '203.0.113.5', 2),
        );
    }

    public function testDifferentSaltYieldsDifferentHash(): void
    {
        self::assertNotSame(
            VisitorHasher::hash(str_repeat("\x01", 32), '203.0.113.5', 1),
            VisitorHasher::hash(str_repeat("\x02", 32), '203.0.113.5', 1),
        );
    }
}
