<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization\Resolution;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Organization\Resolution\OrgResolutionStrategyInterface;
use NeNeRecords\Organization\Resolution\OrgResolverMiddleware;
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
}
