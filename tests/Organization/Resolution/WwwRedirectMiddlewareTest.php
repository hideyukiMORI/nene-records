<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization\Resolution;

use NeNeRecords\Organization\Resolution\WwwRedirectMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class WwwRedirectMiddlewareTest extends TestCase
{
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
        $middleware = new WwwRedirectMiddleware($factory, 'nene-records.com');

        $response = $middleware->process(
            $factory->createServerRequest('GET', 'https://www.nene-records.com/posts/1?ref=x'),
            $this->passthroughHandler(),
        );

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('https://nene-records.com/posts/1?ref=x', $response->getHeaderLine('Location'));
    }

    public function testWwwHostWithPortRedirects(): void
    {
        $factory = new Psr17Factory();
        $middleware = new WwwRedirectMiddleware($factory, 'nene-records.com');

        $response = $middleware->process(
            $factory->createServerRequest('GET', 'https://www.nene-records.com:443/'),
            $this->passthroughHandler(),
        );

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('https://nene-records.com/', $response->getHeaderLine('Location'));
    }

    public function testApexHostPassesThrough(): void
    {
        $factory = new Psr17Factory();
        $middleware = new WwwRedirectMiddleware($factory, 'nene-records.com');

        $response = $middleware->process(
            $factory->createServerRequest('GET', 'https://nene-records.com/'),
            $this->passthroughHandler(),
        );

        self::assertSame(200, $response->getStatusCode());
    }

    public function testTenantSubdomainPassesThrough(): void
    {
        $factory = new Psr17Factory();
        $middleware = new WwwRedirectMiddleware($factory, 'nene-records.com');

        $response = $middleware->process(
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
        $middleware = new WwwRedirectMiddleware($factory, '');

        $response = $middleware->process(
            $factory->createServerRequest('GET', 'https://www.example.com/'),
            $this->passthroughHandler(),
        );

        self::assertSame(200, $response->getStatusCode());
    }
}
