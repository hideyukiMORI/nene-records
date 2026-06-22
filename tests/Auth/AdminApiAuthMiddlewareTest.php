<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Auth;

use Nene2\Auth\TokenVerificationException;
use Nene2\Auth\TokenVerifierInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use NeNeRecords\Auth\AdminApiAuthMiddleware;
use NeNeRecords\Auth\SessionCookie;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AdminApiAuthMiddlewareTest extends TestCase
{
    private Psr17Factory $factory;
    private AdminApiAuthMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Psr17Factory();

        $verifier = new class () implements TokenVerifierInterface {
            /** @return array<string, mixed> */
            public function verify(string $token): array
            {
                if ($token !== 'good') {
                    throw new TokenVerificationException('Invalid token.');
                }

                return ['role' => 'admin', 'sub' => 'admin@example.com'];
            }
        };

        $this->middleware = new AdminApiAuthMiddleware(
            new ProblemDetailsResponseFactory($this->factory, $this->factory),
            $verifier,
        );
    }

    public function testBearerTokenStillAuthenticates(): void
    {
        $request = $this->factory
            ->createServerRequest('POST', 'https://example.test/api/v1/entities')
            ->withHeader('Authorization', 'Bearer good');

        self::assertSame(204, $this->middleware->process($request, $this->passThrough())->getStatusCode());
    }

    public function testCookieAuthenticatesSafeRequest(): void
    {
        // GET on an admin-only path is protected but needs no CSRF header.
        $request = $this->factory
            ->createServerRequest('GET', 'https://example.test/api/v1/users')
            ->withCookieParams([SessionCookie::NAME => 'good']);

        self::assertSame(204, $this->middleware->process($request, $this->passThrough())->getStatusCode());
    }

    public function testCookieMutationWithoutCustomHeaderIsRejectedAsCsrf(): void
    {
        $request = $this->factory
            ->createServerRequest('POST', 'https://example.test/api/v1/entities')
            ->withCookieParams([SessionCookie::NAME => 'good']);

        $response = $this->middleware->process($request, $this->passThrough());

        self::assertSame(403, $response->getStatusCode());
    }

    public function testCookieMutationWithCustomHeaderPasses(): void
    {
        $request = $this->factory
            ->createServerRequest('POST', 'https://example.test/api/v1/entities')
            ->withCookieParams([SessionCookie::NAME => 'good'])
            ->withHeader('X-Requested-With', 'fetch');

        self::assertSame(204, $this->middleware->process($request, $this->passThrough())->getStatusCode());
    }

    public function testMissingTokenReturns401(): void
    {
        $request = $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities');

        self::assertSame(401, $this->middleware->process($request, $this->passThrough())->getStatusCode());
    }

    public function testScheduledPublishCronEndpointIsOpenWithoutAuth(): void
    {
        // The cron container POSTs this without credentials — it must pass through.
        $request = $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/process-scheduled');

        self::assertSame(204, $this->middleware->process($request, $this->passThrough())->getStatusCode());
    }

    public function testProcessWebhookDeliveriesCronEndpointIsOpenWithoutAuth(): void
    {
        $request = $this->factory->createServerRequest('POST', 'https://example.test/api/v1/webhooks/process-deliveries');

        self::assertSame(204, $this->middleware->process($request, $this->passThrough())->getStatusCode());
    }

    public function testOtherWebhookWritesStillRequireAuth(): void
    {
        // Opening process-deliveries must not open the rest of /api/v1/webhooks.
        $request = $this->factory->createServerRequest('POST', 'https://example.test/api/v1/webhooks');

        self::assertSame(401, $this->middleware->process($request, $this->passThrough())->getStatusCode());
    }

    public function testInvalidTokenReturns401(): void
    {
        $request = $this->factory
            ->createServerRequest('POST', 'https://example.test/api/v1/entities')
            ->withCookieParams([SessionCookie::NAME => 'bad'])
            ->withHeader('X-Requested-With', 'fetch');

        self::assertSame(401, $this->middleware->process($request, $this->passThrough())->getStatusCode());
    }

    private function passThrough(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                return new Response(204);
            }
        };
    }
}
