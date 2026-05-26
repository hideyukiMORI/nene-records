<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\RateLimit;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\Middleware\InMemoryRateLimitStorage;
use Nene2\Middleware\ThrottleMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RateLimitTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryRateLimitStorage $storage;
    private ProblemDetailsResponseFactory $problemDetails;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->storage = new InMemoryRateLimitStorage();
        $this->problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);
    }

    private function buildApp(int $limit, int $windowSeconds): RequestHandlerInterface
    {
        $throttle = new ThrottleMiddleware(
            $this->problemDetails,
            $this->storage,
            limit: $limit,
            windowSeconds: $windowSeconds,
        );

        return (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [],
            routeRegistrars: [
                static function (\Nene2\Routing\Router $router): void {
                    $router->get(
                        '/api/v1/ping',
                        static fn (ServerRequestInterface $request): ResponseInterface => new \Nyholm\Psr7\Response(200),
                    );
                },
            ],
            throttleMiddleware: $throttle,
        ))->create();
    }

    public function testRequestsWithinLimitSucceed(): void
    {
        $app = $this->buildApp(limit: 3, windowSeconds: 60);

        for ($i = 0; $i < 3; $i++) {
            $response = $app->handle(
                $this->factory->createServerRequest('GET', 'https://example.test/api/v1/ping'),
            );
            self::assertSame(200, $response->getStatusCode(), "Request $i should succeed");
        }
    }

    public function testRequestExceedingLimitReturns429(): void
    {
        $app = $this->buildApp(limit: 2, windowSeconds: 60);

        // First two should pass
        $app->handle($this->factory->createServerRequest('GET', 'https://example.test/api/v1/ping'));
        $app->handle($this->factory->createServerRequest('GET', 'https://example.test/api/v1/ping'));

        // Third should be blocked
        $response = $app->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/ping'),
        );

        self::assertSame(429, $response->getStatusCode());
        self::assertNotEmpty($response->getHeaderLine('Retry-After'));
    }

    public function testRateLimitHeadersArePresentOnSuccess(): void
    {
        $app = $this->buildApp(limit: 10, windowSeconds: 60);

        $response = $app->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/ping'),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('10', $response->getHeaderLine('X-RateLimit-Limit'));
        self::assertNotEmpty($response->getHeaderLine('X-RateLimit-Remaining'));
        self::assertNotEmpty($response->getHeaderLine('X-RateLimit-Reset'));
    }

    public function testPdoRateLimitStorageHit(): void
    {
        // Test the in-memory storage (used as a stand-in for PDO in unit tests)
        $storage = new InMemoryRateLimitStorage();
        $windowSeconds = 60;

        $result1 = $storage->hit('ip:127.0.0.1', $windowSeconds);
        self::assertSame(1, $result1['count']);
        self::assertGreaterThan(time(), $result1['reset_at']);

        $result2 = $storage->hit('ip:127.0.0.1', $windowSeconds);
        self::assertSame(2, $result2['count']);
        self::assertSame($result1['reset_at'], $result2['reset_at']);
    }
}
