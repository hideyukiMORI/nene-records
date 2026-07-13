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

    public function testSuperadminExportGetRequiresAuth(): void
    {
        // GET export reads every tenant's data; it is not a "non-GET" mutation,
        // so it must be gated by the admin-only prefix, not left open. See #797.
        $request = $this->factory->createServerRequest('GET', 'https://example.test/api/v1/superadmin/organizations/1/export');

        self::assertSame(401, $this->middleware->process($request, $this->passThrough())->getStatusCode());
    }

    public function testSuperadminSystemConfigGetRequiresAuth(): void
    {
        $request = $this->factory->createServerRequest('GET', 'https://example.test/api/v1/superadmin/system-config');

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

    public function testSensitiveUnauthenticatedGetIsRejected(): void
    {
        // #824: sensitive reads (webhook signing secrets, notification-channel
        // configs, bulk export, org dashboard metrics) must require auth even for
        // GET — they are in ADMIN_ONLY_PREFIXES.
        foreach (['/api/v1/webhooks', '/api/v1/notification-channels', '/api/v1/entities/export', '/api/v1/dashboard'] as $path) {
            $request = $this->factory->createServerRequest('GET', 'https://example.test' . $path);
            self::assertSame(
                401,
                $this->middleware->process($request, $this->passThrough())->getStatusCode(),
                sprintf('Unauthenticated GET %s must be 401', $path),
            );
        }
    }

    public function testPublicContentGetSurfaceStaysOpen(): void
    {
        // #826: the consumer site reads content unauthenticated to render public
        // pages — these GETs must stay open (protecting them broke the public site).
        foreach (['/api/v1/entities', '/api/v1/text-fields', '/api/v1/entity-types', '/api/v1/tags', '/api/v1/analytics/popular-entities'] as $path) {
            $request = $this->factory->createServerRequest('GET', 'https://example.test' . $path);
            self::assertSame(
                204,
                $this->middleware->process($request, $this->passThrough())->getStatusCode(),
                sprintf('Unauthenticated GET %s must stay open (public content read)', $path),
            );
        }
    }

    public function testPublicApiGetRemainsOpen(): void
    {
        // The dedicated public surface (/api/v1/public/*) stays unauthenticated.
        $request = $this->factory->createServerRequest('GET', 'https://example.test/api/v1/public/settings');

        self::assertSame(204, $this->middleware->process($request, $this->passThrough())->getStatusCode());
    }

    public function testCorsPreflightOptionsIsOpen(): void
    {
        // OPTIONS carries no credentials (CORS preflight) and must pass through.
        $request = $this->factory->createServerRequest('OPTIONS', 'https://example.test/api/v1/entities');

        self::assertSame(204, $this->middleware->process($request, $this->passThrough())->getStatusCode());
    }

    public function testOpenGetRouteAttachesClaimsForValidToken(): void
    {
        // #828: on an open content-read route, a valid token must still attach claims
        // so downstream handlers can widen results for an authenticated admin.
        $capture = new class () implements RequestHandlerInterface {
            /** @var array<string, mixed>|null */
            public ?array $claims = null;

            public function handle(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $claims = $request->getAttribute('nene2.auth.claims');
                $this->claims = is_array($claims) ? $claims : null;

                return new Response(204);
            }
        };

        $request = $this->factory
            ->createServerRequest('GET', 'https://example.test/api/v1/entities')
            ->withHeader('Authorization', 'Bearer good');

        self::assertSame(204, $this->middleware->process($request, $capture)->getStatusCode());
        self::assertIsArray($capture->claims);
        self::assertSame('admin', $capture->claims['role'] ?? null);
    }

    public function testOpenGetRouteWithInvalidTokenServesAnonymously(): void
    {
        // Invalid/expired token on an open route → no 401, no claims (anonymous).
        $capture = new class () implements RequestHandlerInterface {
            public bool $handled = false;
            public mixed $claims = 'unset';

            public function handle(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $this->handled = true;
                $this->claims = $request->getAttribute('nene2.auth.claims');

                return new Response(204);
            }
        };

        $request = $this->factory
            ->createServerRequest('GET', 'https://example.test/api/v1/entities')
            ->withHeader('Authorization', 'Bearer bad');

        self::assertSame(204, $this->middleware->process($request, $capture)->getStatusCode());
        self::assertTrue($capture->handled);
        self::assertNull($capture->claims);
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
