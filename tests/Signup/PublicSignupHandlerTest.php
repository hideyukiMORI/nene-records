<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Signup;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Middleware\InMemoryRateLimitStorage;
use Nene2\Validation\ValidationException;
use NeNeRecords\Signup\PublicSignupHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class PublicSignupHandlerTest extends TestCase
{
    private Psr17Factory $factory;
    private RecordingPublicSignupUseCase $useCase;
    private PublicSignupHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->useCase = new RecordingPublicSignupUseCase();

        // Small window limit (2) keeps the throttle tests fast and explicit.
        $this->handler = new PublicSignupHandler(
            $this->useCase,
            new JsonResponseFactory($this->factory, $this->factory),
            new ProblemDetailsResponseFactory($this->factory, $this->factory),
            new InMemoryRateLimitStorage(),
            maxSignupsPerWindow: 2,
            rateLimitWindowSeconds: 3600,
        );
    }

    public function testValidSignupReturns201(): void
    {
        $response = $this->handler->handle($this->signupRequest('shop-a', '203.0.113.7'));

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(1, $this->useCase->calls);
    }

    public function testExceedingTheLimitReturns429(): void
    {
        self::assertSame(201, $this->handler->handle($this->signupRequest('shop-a', '203.0.113.7'))->getStatusCode());
        self::assertSame(201, $this->handler->handle($this->signupRequest('shop-b', '203.0.113.7'))->getStatusCode());

        // The 3rd within the window (limit 2) is throttled.
        $third = $this->handler->handle($this->signupRequest('shop-c', '203.0.113.7'));

        self::assertSame(429, $third->getStatusCode());
        self::assertNotSame('', $third->getHeaderLine('Retry-After'));
        self::assertSame('2', $third->getHeaderLine('X-RateLimit-Limit'));
        // The throttled attempt must never reach provisioning.
        self::assertSame(2, $this->useCase->calls);
    }

    public function testRateLimitIsPerClientIp(): void
    {
        $this->handler->handle($this->signupRequest('shop-a', '203.0.113.7'));
        $this->handler->handle($this->signupRequest('shop-b', '203.0.113.7'));
        self::assertSame(429, $this->handler->handle($this->signupRequest('shop-c', '203.0.113.7'))->getStatusCode());

        // A different client IP keeps its own budget.
        self::assertSame(201, $this->handler->handle($this->signupRequest('shop-d', '198.51.100.4'))->getStatusCode());
    }

    public function testSpoofedForwardedForCannotEvadeTheLimit(): void
    {
        // Same real last hop, a different forged leftmost entry each time.
        $this->handler->handle($this->signupRequest('shop-a', '203.0.113.7', '1.1.1.1'));
        $this->handler->handle($this->signupRequest('shop-b', '203.0.113.7', '2.2.2.2'));
        $third = $this->handler->handle($this->signupRequest('shop-c', '203.0.113.7', '3.3.3.3'));

        self::assertSame(429, $third->getStatusCode());
    }

    public function testMalformedRequestsDoNotConsumeBudget(): void
    {
        // Two malformed attempts fail validation before the rate-limit gate.
        for ($i = 0; $i < 2; ++$i) {
            try {
                $this->handler->handle($this->malformedRequest('203.0.113.7'));
                self::fail('Expected ValidationException for malformed signup.');
            } catch (ValidationException) {
                // expected — must not spend the rate-limit budget
            }
        }

        // The full budget (2) is still available for well-formed signups.
        self::assertSame(201, $this->handler->handle($this->signupRequest('shop-a', '203.0.113.7'))->getStatusCode());
        self::assertSame(201, $this->handler->handle($this->signupRequest('shop-b', '203.0.113.7'))->getStatusCode());
        self::assertSame(429, $this->handler->handle($this->signupRequest('shop-c', '203.0.113.7'))->getStatusCode());
    }

    private function signupRequest(string $slug, string $clientIp, ?string $spoofedLeftmost = null): ServerRequestInterface
    {
        $body = $this->factory->createStream(json_encode([
            'organization_name' => 'My Shop',
            'slug'              => $slug,
            'email'             => $slug . '@example.com',
            'password'          => 'a-strong-password',
        ], JSON_THROW_ON_ERROR));

        $forwardedFor = $spoofedLeftmost === null ? $clientIp : $spoofedLeftmost . ', ' . $clientIp;

        return $this->factory
            ->createServerRequest('POST', 'https://apex.example.test/api/v1/public/signup')
            ->withHeader('X-Forwarded-For', $forwardedFor)
            ->withBody($body);
    }

    private function malformedRequest(string $clientIp): ServerRequestInterface
    {
        $body = $this->factory->createStream(json_encode([
            'organization_name' => '',
            'slug'              => '',
            'email'             => 'not-an-email',
            'password'          => 'short',
        ], JSON_THROW_ON_ERROR));

        return $this->factory
            ->createServerRequest('POST', 'https://apex.example.test/api/v1/public/signup')
            ->withHeader('X-Forwarded-For', $clientIp)
            ->withBody($body);
    }
}
