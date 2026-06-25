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
        $repository = new InMemoryOrganizationRepository();
        $repository->save(new Organization('My Shop', 'myshop', 'free', true));

        $middleware = new OrgResolverMiddleware(
            $orgId,
            $repository,
            new ProblemDetailsResponseFactory($factory, $factory),
            new PathPrefixResolutionStrategy(),
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

        $middleware = new OrgResolverMiddleware(
            $orgId,
            new InMemoryOrganizationRepository(),
            new ProblemDetailsResponseFactory($factory, $factory),
            new SubdomainResolutionStrategy('nene-records.com'),
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
    }

    public function testSubdomainUnknownTenantStill404s(): void
    {
        $factory = new Psr17Factory();
        /** @var RequestScopedHolder<int> $orgId */
        $orgId = new RequestScopedHolder();

        $middleware = new OrgResolverMiddleware(
            $orgId,
            new InMemoryOrganizationRepository(), // empty → "nope" not found
            new ProblemDetailsResponseFactory($factory, $factory),
            new SubdomainResolutionStrategy('nene-records.com'),
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
    }
}
