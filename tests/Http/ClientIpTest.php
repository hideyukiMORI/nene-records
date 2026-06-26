<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

use NeNeRecords\Http\ClientIp;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class ClientIpTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Psr17Factory();
    }

    public function testFallsBackToRemoteAddrWhenNoForwardedHeader(): void
    {
        $request = $this->factory->createServerRequest('POST', 'https://example.test/x', ['REMOTE_ADDR' => '203.0.113.7']);

        self::assertSame('203.0.113.7', ClientIp::resolve($request));
    }

    public function testUsesForwardedForOverRemoteAddr(): void
    {
        $request = $this->factory
            ->createServerRequest('POST', 'https://example.test/x', ['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeader('X-Forwarded-For', '203.0.113.7');

        self::assertSame('203.0.113.7', ClientIp::resolve($request));
    }

    public function testTakesLastForwardedHopToResistSpoofing(): void
    {
        // A client may prepend a forged entry; our trusted proxy appends the real
        // peer as the LAST hop, so the rightmost address is the trustworthy one.
        $request = $this->factory
            ->createServerRequest('POST', 'https://example.test/x', ['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeader('X-Forwarded-For', '9.9.9.9, 203.0.113.7');

        self::assertSame('203.0.113.7', ClientIp::resolve($request));
    }

    public function testTrimsWhitespaceInForwardedChain(): void
    {
        $request = $this->factory
            ->createServerRequest('POST', 'https://example.test/x', ['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeader('X-Forwarded-For', '9.9.9.9 ,  203.0.113.7 ');

        self::assertSame('203.0.113.7', ClientIp::resolve($request));
    }

    public function testReturnsUnknownWhenNothingIsAvailable(): void
    {
        $request = $this->factory->createServerRequest('POST', 'https://example.test/x');

        self::assertSame('unknown', ClientIp::resolve($request));
    }
}
