<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Auth;

use NeNeRecords\Auth\SessionCookie;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class SessionCookieTest extends TestCase
{
    public function testBuildSetsHttpOnlyLaxAndMaxAge(): void
    {
        $cookie = SessionCookie::build('tok', 3600, false);

        self::assertStringContainsString('nene_session=tok', $cookie);
        self::assertStringContainsString('HttpOnly', $cookie);
        self::assertStringContainsString('SameSite=Lax', $cookie);
        self::assertStringContainsString('Max-Age=3600', $cookie);
        self::assertStringNotContainsString('Secure', $cookie);
    }

    public function testBuildAddsSecureOverHttps(): void
    {
        self::assertStringContainsString('Secure', SessionCookie::build('tok', 3600, true));
    }

    public function testClearExpiresImmediately(): void
    {
        self::assertStringContainsString('Max-Age=0', SessionCookie::clear(false));
    }

    public function testNegativeMaxAgeIsClampedToZero(): void
    {
        self::assertStringContainsString('Max-Age=0', SessionCookie::build('tok', -10, false));
    }

    public function testIsSecureRequestDetectsForwardedProto(): void
    {
        $factory = new Psr17Factory();
        $http = $factory->createServerRequest('GET', 'http://example.test/');
        $forwarded = $http->withHeader('X-Forwarded-Proto', 'https');

        self::assertFalse(SessionCookie::isSecureRequest($http));
        self::assertTrue(SessionCookie::isSecureRequest($forwarded));
    }
}
