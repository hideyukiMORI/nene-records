<?php

declare(strict_types=1);

namespace NeNeRecords\Organization\Resolution;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Resolves the current organization from the request and stores its ID
 * in a RequestScopedHolder for downstream repositories to read.
 *
 * Bypass paths (superadmin org management, health, auth) skip resolution but
 * still seed the holder with 0 — the no-org sentinel (access_logs.organization_id
 * is NOT NULL DEFAULT 0) — so downstream request-scoped readers such as the
 * access-log writer don't fault on these org-agnostic routes.
 *
 * Resolution order:
 *  1. strategy->resolve() → slug or custom domain identifier
 *  2. OrganizationRepository::findBySlug() → Organization
 *  3. If not found by slug, try findByCustomDomain() (for CustomDomainStrategy)
 *  4. 404 if still not found
 */
final readonly class OrgResolverMiddleware implements MiddlewareInterface
{
    /**
     * Paths that bypass org resolution entirely.
     * Superadmin routes, health checks, and login do not need an org context.
     *
     * @var list<string>
     */
    private const BYPASS_PREFIXES = [
        '/health',
        '/internal/tls-check',
        '/api/v1/organizations',
        '/api/v1/superadmin/',
        '/api/v1/auth/',
    ];

    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private RequestScopedHolder $orgId,
        private OrganizationRepositoryInterface $repository,
        private ProblemDetailsResponseFactory $problemDetails,
        private OrgResolutionStrategyInterface $strategy,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        // Bypass: superadmin / health / auth routes don't need org context, but
        // downstream request-scoped readers (e.g. the access-log writer) still read
        // the holder — seed it with 0 (the no-org sentinel) so they don't fault.
        foreach (self::BYPASS_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $this->orgId->set(0);

                return $handler->handle($request);
            }
        }

        $identifier = $this->strategy->resolve($request);

        // Strategy returned null (e.g. EnvResolutionStrategy with no ORG_SLUG set)
        if ($identifier === null) {
            return $this->problemDetails->create(
                $request,
                'org-not-resolved',
                'Organization Not Resolved',
                404,
                'Could not determine the organization for this request. Check your TENANT_RESOLUTION configuration.',
            );
        }

        // Try by slug first, then by custom domain
        $org = $this->repository->findBySlug($identifier)
            ?? $this->repository->findByCustomDomain($identifier);

        if ($org === null) {
            return $this->problemDetails->create(
                $request,
                'org-not-found',
                'Organization Not Found',
                404,
                "No organization found for '{$identifier}'.",
            );
        }

        if (!$org->isActive) {
            return $this->problemDetails->create(
                $request,
                'org-inactive',
                'Organization Inactive',
                403,
                'This organization is currently inactive.',
            );
        }

        $this->orgId->set($org->id ?? 0);

        // Directory / path mode: strip the tenant's leading path segment so the
        // router sees `/posts/1`, and expose it on `nene2.base_prefix` so public
        // URL generation re-adds it (canonical / sitemap / <base href>). Other
        // strategies (subdomain / env / custom domain) carry an empty prefix.
        $basePrefix = $this->strategy instanceof UriPrefixStrippingStrategyInterface
            ? $this->strategy->basePrefix($request)
            : '';

        $request = $request
            ->withAttribute('nene2.org.id', $org->id)
            ->withAttribute('nene2.org.slug', $org->slug)
            ->withAttribute('nene2.base_prefix', $basePrefix);

        if ($basePrefix !== '') {
            $uri = $request->getUri();
            $stripped = substr($uri->getPath(), strlen($basePrefix));
            $request = $request->withUri($uri->withPath($stripped === '' ? '/' : $stripped));
        }

        return $handler->handle($request);
    }
}
