<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Signup;

use NeNeRecords\Signup\ReservedSlugs;
use PHPUnit\Framework\TestCase;

final class ReservedSlugsTest extends TestCase
{
    public function testValidFormats(): void
    {
        self::assertTrue(ReservedSlugs::isValidFormat('shop'));
        self::assertTrue(ReservedSlugs::isValidFormat('my-shop'));
        self::assertTrue(ReservedSlugs::isValidFormat('shop2024'));
        self::assertTrue(ReservedSlugs::isValidFormat('a1b'));
    }

    public function testInvalidFormats(): void
    {
        self::assertFalse(ReservedSlugs::isValidFormat('ab'));        // too short
        self::assertFalse(ReservedSlugs::isValidFormat('-shop'));     // leading hyphen
        self::assertFalse(ReservedSlugs::isValidFormat('shop-'));     // trailing hyphen
        self::assertFalse(ReservedSlugs::isValidFormat('Shop'));      // uppercase
        self::assertFalse(ReservedSlugs::isValidFormat('my_shop'));   // underscore
        self::assertFalse(ReservedSlugs::isValidFormat(str_repeat('a', 31))); // too long
    }

    public function testReservedLabels(): void
    {
        self::assertTrue(ReservedSlugs::isReserved('www'));
        self::assertTrue(ReservedSlugs::isReserved('api'));
        self::assertTrue(ReservedSlugs::isReserved('admin'));
        self::assertTrue(ReservedSlugs::isReserved('mail'));
        self::assertFalse(ReservedSlugs::isReserved('shop'));
    }

    public function testIsAvailableCombinesBoth(): void
    {
        self::assertTrue(ReservedSlugs::isAvailable('my-cafe'));
        self::assertFalse(ReservedSlugs::isAvailable('admin'));   // reserved
        self::assertFalse(ReservedSlugs::isAvailable('-bad'));    // malformed
    }
}
