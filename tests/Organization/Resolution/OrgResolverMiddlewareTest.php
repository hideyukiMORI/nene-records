<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization\Resolution;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Organization\Organization;
use NeNeRecords\Organization\Resolution\OrgResolutionStrategyInterface;
use NeNeRecords\Organization\Resolution\OrgResolverMiddleware;
use NeNeRecords\Organization\Resolution\PathPrefixResolutionStrategy;
use NeNeRecords\Organization\Resolution\SubdomainResolutionStrategy;
use NeNeRecords\Tests\Organization\InMemoryOrganizationRepository;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class OrgResolverMiddlewareTest extends TestCase
{
    /**
     * Bypass routes (auth / superadmin / org management / health) carry no tenant,
     * but the holder must still be seeded with 0 (the no-org sentinel) so that
     * downstream request-scoped readers — notably the access-log writer — don't
     * fault with "RequestScopedHolder::get() called before set()". Regression for #528.
     */
    public function testBypassRouteSeedsOrgIdWithZero(): void
    {
        $factory = new Psr17Factory();
        /** @var RequestScopedHolder<int> $orgId */
        $orgId = new RequestScopedHolder();
        /** @var RequestScopedHolder<bool> $orgMissing */
        $orgMissing = new RequestScopedHolder();

        $middleware = new OrgResolverMiddleware(
            $orgId,
            new InMemoryOrganizationRepository(),
            new ProblemDetailsResponseFactory($factory, $factory),
            new class () implements OrgResolutionStrategyInterface {
                public function resolve(ServerRequestInterface $request): ?string
                {
                    return null;
                }
            },
            $orgMissing,
        );

        $response = $middleware->process(
            $factory->createServerRequest('POST', 'https://example.test/api/v1/auth/login'),
            new readonly class ($factory) implements RequestHandlerInterface {
                public function __construct(private Psr17Factory $factory)
                {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->factory->createResponse(200);
                }
            },
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(0, $orgId->get());
        // A bypass route never names a tenant, so it is not a *missing* one (#1057).
        self::assertFalse($orgMissing->isSet());
    }

    /**
     * Directory / path mode: the tenant's leading path segment is stripped before
     * routing (downstream sees /posts/1) and re-exposed on nene2.base_prefix so
     * public URL generation can re-add it. #536 base-path S-path.
     */
    public function testPathModeStripsPrefixAndExposesBasePrefix(): void
    {
        $factory = new Psr17Factory();
        /** @var RequestScopedHolder<int> $orgId */
        $orgId = new RequestScopedHolder();
        /** @var RequestScopedHolder<bool> $orgMissing */
        $orgMissing = new RequestScopedHolder();
        $repository = new InMemoryOrganizationRepository();
        $repository->save(new Organization('My Shop', 'myshop', 'free', true));

        $middleware = new OrgResolverMiddleware(
            $orgId,
            $repository,
            new ProblemDetailsResponseFactory($factory, $factory),
            new PathPrefixResolutionStrategy(),
            $orgMissing,
        );

        $capture = new class ($factory) implements RequestHandlerInterface {
            public ?ServerRequestInterface $seen = null;

            public function __construct(private readonly Psr17Factory $factory)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->seen = $request;

                return $this->factory->createResponse(200);
            }
        };

        $middleware->process(
            $factory->createServerRequest('GET', 'https://example.test/myshop/posts/1'),
            $capture,
        );

        self::assertNotNull($capture->seen);
        // Tenant segment stripped for routing…
        self::assertSame('/posts/1', $capture->seen->getUri()->getPath());
        // …and re-exposed for URL generation, alongside the resolved org.
        self::assertSame('/myshop', $capture->seen->getAttribute('nene2.base_prefix'));
        self::assertSame('myshop', $capture->seen->getAttribute('nene2.org.slug'));
        self::assertFalse($orgMissing->isSet());
    }

    /**
     * Subdomain SaaS apex (host === base domain) carries no tenant but must serve
     * the global landing / signup surface, not 404. #536 subdomain-saas ②.
     */
    public function testSubdomainApexServesGlobalSurfaceAsNoTenant(): void
    {
        $factory = new Psr17Factory();
        /** @var RequestScopedHolder<int> $orgId */
        $orgId = new RequestScopedHolder();
        /** @var RequestScopedHolder<bool> $orgMissing */
        $orgMissing = new RequestScopedHolder();

        $middleware = new OrgResolverMiddleware(
            $orgId,
            new InMemoryOrganizationRepository(),
            new ProblemDetailsResponseFactory($factory, $factory),
            new SubdomainResolutionStrategy('nene-records.com'),
            $orgMissing,
        );

        $capture = new class ($factory) implements RequestHandlerInterface {
            public ?ServerRequestInterface $seen = null;

            public function __construct(private readonly Psr17Factory $factory)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->seen = $request;

                return $this->factory->createResponse(200);
            }
        };

        $response = $middleware->process(
            $factory->createServerRequest('GET', 'https://nene-records.com/'),
            $capture,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(0, $orgId->get()); // no-tenant sentinel
        self::assertNotNull($capture->seen);
        self::assertTrue($capture->seen->getAttribute('nene2.apex'));
        // The apex is a real surface (landing / signup), not a missing tenant (#1057).
        self::assertFalse($orgMissing->isSet());
    }

    public function testSubdomainUnknownTenantStill404s(): void
    {
        $factory = new Psr17Factory();
        /** @var RequestScopedHolder<int> $orgId */
        $orgId = new RequestScopedHolder();
        /** @var RequestScopedHolder<bool> $orgMissing */
        $orgMissing = new RequestScopedHolder();

        $middleware = new OrgResolverMiddleware(
            $orgId,
            new InMemoryOrganizationRepository(), // empty → "nope" not found
            new ProblemDetailsResponseFactory($factory, $factory),
            new SubdomainResolutionStrategy('nene-records.com'),
            $orgMissing,
        );

        $response = $middleware->process(
            $factory->createServerRequest('GET', 'https://nope.nene-records.com/'),
            new readonly class ($factory) implements RequestHandlerInterface {
                public function __construct(private Psr17Factory $factory)
                {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->factory->createResponse(200);
                }
            },
        );

        self::assertSame(404, $response->getStatusCode());
        // #1057: the definite negative the single-origin edge layers read, so the
        // 404 is not repainted as a 200 SPA shell for `/` and `/login`.
        self::assertTrue($orgMissing->isSet());
        self::assertTrue($orgMissing->get());
    }

    /**
     * #1057: an inactive org is equally unserviceable. Its 403 never reaches the SPA
     * shell today (the shell only fills 404s), but the flag must be consistent so a
     * later change to that status — #973 proposes 404 — cannot silently reintroduce
     * a 200 for a suspended tenant.
     */
    public function testInactiveTenantIsFlaggedAsUnserviceable(): void
    {
        $factory = new Psr17Factory();
        /** @var RequestScopedHolder<int> $orgId */
        $orgId = new RequestScopedHolder();
        /** @var RequestScopedHolder<bool> $orgMissing */
        $orgMissing = new RequestScopedHolder();
        $repository = new InMemoryOrganizationRepository();
        $repository->save(new Organization('Suspended', 'suspended', 'free', false));

        $middleware = new OrgResolverMiddleware(
            $orgId,
            $repository,
            new ProblemDetailsResponseFactory($factory, $factory),
            new SubdomainResolutionStrategy('nene-records.com'),
            $orgMissing,
        );

        $response = $middleware->process(
            $factory->createServerRequest('GET', 'https://suspended.nene-records.com/'),
            $this->passThrough($factory),
        );

        self::assertSame(403, $response->getStatusCode());
        self::assertTrue($orgMissing->get());
    }

    /**
     * #1057: a configuration gap is NOT a missing tenant. `org-not-resolved` means the
     * deployment has no ORG_SLUG yet — flagging it would stop the SPA shell serving
     * `/admin` and `/login`, locking the operator out of the surface they need to fix
     * it. The 404 stays, the flag does not.
     */
    public function testUnconfiguredDeploymentIsNotFlaggedAsMissingOrg(): void
    {
        $factory = new Psr17Factory();
        /** @var RequestScopedHolder<int> $orgId */
        $orgId = new RequestScopedHolder();
        /** @var RequestScopedHolder<bool> $orgMissing */
        $orgMissing = new RequestScopedHolder();

        $middleware = new OrgResolverMiddleware(
            $orgId,
            new InMemoryOrganizationRepository(),
            new ProblemDetailsResponseFactory($factory, $factory),
            new class () implements OrgResolutionStrategyInterface {
                public function resolve(ServerRequestInterface $request): ?string
                {
                    return null; // e.g. EnvResolutionStrategy with no ORG_SLUG
                }
            },
            $orgMissing,
        );

        $response = $middleware->process(
            $factory->createServerRequest('GET', 'https://example.test/admin/dashboard'),
            $this->passThrough($factory),
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertFalse($orgMissing->isSet());
    }

    /** A handler that answers 200 for anything that gets past the middleware. */
    private function passThrough(Psr17Factory $factory): RequestHandlerInterface
    {
        return new readonly class ($factory) implements RequestHandlerInterface {
            public function __construct(private Psr17Factory $factory)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->factory->createResponse(200);
            }
        };
    }
}
