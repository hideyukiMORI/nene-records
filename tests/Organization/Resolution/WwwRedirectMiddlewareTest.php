<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization\Resolution;

use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Organization\Resolution\WwwRedirectMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class WwwRedirectMiddlewareTest extends TestCase
{
    /** @var RequestScopedHolder<int> */
    private RequestScopedHolder $orgId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orgId = new RequestScopedHolder();
    }

    private function middleware(string $baseDomain): WwwRedirectMiddleware
    {
        return new WwwRedirectMiddleware(new Psr17Factory(), $this->orgId, $baseDomain);
    }

    private function passthroughHandler(): RequestHandlerInterface
    {
        return new readonly class (new Psr17Factory()) implements RequestHandlerInterface {
            public function __construct(private Psr17Factory $factory)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->factory->createResponse(200);
            }
        };
    }

    /**
     * `www.<base>` must 301 to the apex, preserving path + query. Regression for #832.
     */
    public function testWwwHostRedirectsToApex(): void
    {
        $factory = new Psr17Factory();

        $response = $this->middleware('nene-records.com')->process(
            $factory->createServerRequest('GET', 'https://www.nene-records.com/posts/1?ref=x'),
            $this->passthroughHandler(),
        );

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('https://nene-records.com/posts/1?ref=x', $response->getHeaderLine('Location'));
    }

    /**
     * The redirect short-circuits ahead of OrgResolverMiddleware, so the org holder
     * must be seeded with the no-org sentinel here or the access-log writer that
     * wraps this middleware faults on every www request (#528 pattern, #834).
     */
    public function testWwwRedirectSeedsNoOrgSentinel(): void
    {
        $factory = new Psr17Factory();

        $this->middleware('nene-records.com')->process(
            $factory->createServerRequest('GET', 'https://www.nene-records.com/'),
            $this->passthroughHandler(),
        );

        self::assertSame(0, $this->orgId->get());
    }

    public function testWwwHostWithPortRedirects(): void
    {
        $factory = new Psr17Factory();

        $response = $this->middleware('nene-records.com')->process(
            $factory->createServerRequest('GET', 'https://www.nene-records.com:443/'),
            $this->passthroughHandler(),
        );

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('https://nene-records.com/', $response->getHeaderLine('Location'));
    }

    public function testApexHostPassesThrough(): void
    {
        $factory = new Psr17Factory();

        $response = $this->middleware('nene-records.com')->process(
            $factory->createServerRequest('GET', 'https://nene-records.com/'),
            $this->passthroughHandler(),
        );

        self::assertSame(200, $response->getStatusCode());
    }

    public function testTenantSubdomainPassesThrough(): void
    {
        $factory = new Psr17Factory();

        $response = $this->middleware('nene-records.com')->process(
            $factory->createServerRequest('GET', 'https://shop.nene-records.com/'),
            $this->passthroughHandler(),
        );

        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * Single/path mode: baseDomain unset/empty → no-op, even for a `www` host.
     */
    public function testEmptyBaseDomainIsNoOp(): void
    {
        $factory = new Psr17Factory();

        $response = $this->middleware('')->process(
            $factory->createServerRequest('GET', 'https://www.example.com/'),
            $this->passthroughHandler(),
        );

        self::assertSame(200, $response->getStatusCode());
    }
}
