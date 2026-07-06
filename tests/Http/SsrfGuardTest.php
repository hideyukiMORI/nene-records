<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

use NeNeRecords\Http\SsrfGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * SSRF guard tests. Every blocked case is rejected by the IP-range / scheme check
 * before any network call, so these run offline and deterministically. Literal-IP
 * hosts are used for the allowed cases so no DNS lookup is needed either.
 */
final class SsrfGuardTest extends TestCase
{
    private SsrfGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = new SsrfGuard();
    }

    /** @return list<array{string}> */
    public static function publicIps(): array
    {
        return [['8.8.8.8'], ['1.1.1.1'], ['203.0.113.10'], ['2001:4860:4860::8888']];
    }

    /** @return list<array{string}> */
    public static function blockedIps(): array
    {
        return [
            ['127.0.0.1'],       // loopback
            ['::1'],             // IPv6 loopback
            ['10.0.0.5'],        // private
            ['172.16.0.1'],      // private
            ['192.168.1.1'],     // private
            ['169.254.169.254'], // link-local / cloud metadata
            ['100.64.0.1'],      // carrier-grade NAT (RFC 6598)
            ['0.0.0.0'],         // reserved
            ['fc00::1'],         // IPv6 unique-local
            ['fe80::1'],         // IPv6 link-local
            ['::ffff:127.0.0.1'], // IPv4-mapped loopback
        ];
    }

    #[DataProvider('publicIps')]
    public function testPublicIpsAreRoutable(string $ip): void
    {
        self::assertTrue(SsrfGuard::isPublicIp($ip));
    }

    #[DataProvider('blockedIps')]
    public function testPrivateAndReservedIpsAreBlocked(string $ip): void
    {
        self::assertFalse(SsrfGuard::isPublicIp($ip));
    }

    public function testInspectRejectsCloudMetadataEndpoint(): void
    {
        $inspection = $this->guard->inspect('http://169.254.169.254/latest/meta-data/iam/');

        self::assertFalse($inspection->allowed);
        self::assertNotNull($inspection->reason);
        self::assertSame([], $inspection->addresses);
    }

    #[DataProvider('blockedIps')]
    public function testInspectRejectsInternalLiteralHosts(string $ip): void
    {
        $host = str_contains($ip, ':') ? '[' . $ip . ']' : $ip;

        self::assertFalse($this->guard->inspect('http://' . $host . '/hook')->allowed);
        self::assertFalse($this->guard->inspect('https://' . $host . '/hook')->allowed);
    }

    public function testInspectRejectsNonHttpSchemes(): void
    {
        self::assertFalse($this->guard->inspect('file:///etc/passwd')->allowed);
        self::assertFalse($this->guard->inspect('ftp://8.8.8.8/x')->allowed);
        self::assertFalse($this->guard->inspect('gopher://127.0.0.1/')->allowed);
        self::assertFalse($this->guard->inspect('dict://8.8.8.8:11211/')->allowed);
    }

    public function testInspectRejectsMalformedUrl(): void
    {
        self::assertFalse($this->guard->inspect('not a url')->allowed);
        self::assertFalse($this->guard->inspect('http://')->allowed);
    }

    public function testInspectAllowsPublicHostsAndReturnsPinnableAddress(): void
    {
        $v4 = $this->guard->inspect('https://8.8.8.8/webhook');
        self::assertTrue($v4->allowed);
        self::assertNull($v4->reason);
        self::assertSame(['8.8.8.8'], $v4->addresses);

        $http = $this->guard->inspect('http://203.0.113.10:8080/webhook');
        self::assertTrue($http->allowed);
        self::assertSame(['203.0.113.10'], $http->addresses);

        $v6 = $this->guard->inspect('https://[2001:4860:4860::8888]/webhook');
        self::assertTrue($v6->allowed);
        self::assertSame(['2001:4860:4860::8888'], $v6->addresses);
    }

    public function testIsLikelySafeUrlGuardsSchemeAndLiteralHosts(): void
    {
        // rejected: bad scheme / literal internal hosts
        self::assertFalse($this->guard->isLikelySafeUrl('ftp://8.8.8.8/x'));
        self::assertFalse($this->guard->isLikelySafeUrl('http://127.0.0.1/hook'));
        self::assertFalse($this->guard->isLikelySafeUrl('http://169.254.169.254/'));
        self::assertFalse($this->guard->isLikelySafeUrl('http://[::1]/hook'));

        // allowed: public literal IP and hostnames (deferred to egress inspect())
        self::assertTrue($this->guard->isLikelySafeUrl('https://8.8.8.8/hook'));
        self::assertTrue($this->guard->isLikelySafeUrl('https://hooks.example.com/hook'));
    }
}
