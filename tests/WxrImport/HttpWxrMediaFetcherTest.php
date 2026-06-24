<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\WxrImport;

use NeNeRecords\WxrImport\HttpWxrMediaFetcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * SSRF guard tests. Every blocked case is rejected before any network call
 * (the IP-range check fails first), so these run offline and deterministically.
 */
final class HttpWxrMediaFetcherTest extends TestCase
{
    private HttpWxrMediaFetcher $fetcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fetcher = new HttpWxrMediaFetcher();
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
            ['0.0.0.0'],         // reserved
            ['fc00::1'],         // IPv6 unique-local
        ];
    }

    #[DataProvider('publicIps')]
    public function testPublicIpsAreRoutable(string $ip): void
    {
        self::assertTrue(HttpWxrMediaFetcher::isPublicIp($ip));
    }

    #[DataProvider('blockedIps')]
    public function testPrivateAndReservedIpsAreBlocked(string $ip): void
    {
        self::assertFalse(HttpWxrMediaFetcher::isPublicIp($ip));
    }

    #[DataProvider('blockedIps')]
    public function testFetchRefusesInternalHostsWithoutNetwork(string $ip): void
    {
        $host = str_contains($ip, ':') ? '[' . $ip . ']' : $ip;

        self::assertNull($this->fetcher->fetch('http://' . $host . '/latest/meta-data/'));
    }

    public function testFetchRejectsNonHttpSchemes(): void
    {
        self::assertNull($this->fetcher->fetch('file:///etc/passwd'));
        self::assertNull($this->fetcher->fetch('ftp://example.com/x'));
        self::assertNull($this->fetcher->fetch('gopher://127.0.0.1/'));
    }

    public function testFetchRejectsMalformedUrl(): void
    {
        self::assertNull($this->fetcher->fetch('not a url'));
        self::assertNull($this->fetcher->fetch('http://'));
    }
}
