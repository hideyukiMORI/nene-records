<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Enforces role-based capabilities and organization scoping on authenticated API requests.
 *
 * Runs after AdminApiAuthMiddleware. Unauthenticated requests pass through unchanged.
 *
 * Organization scoping rules:
 *  - superadmin: no org check — can operate across all organizations
 *  - admin / editor: JWT org_id must match the resolved org ID (nene2.org.id request attribute)
 *    If the org has not been resolved for this route (bypass paths), the check is skipped.
 */
final readonly class CapabilityMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $claims = $request->getAttribute('nene2.auth.claims');

        if (!is_array($claims)) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath() ?: '/';
        $required = CapabilityResolver::resolve($path, $request->getMethod());

        if ($required === null) {
            return $handler->handle($request);
        }

        $role = Role::tryFrom((string) ($claims['role'] ?? ''));

        if ($role === null || !$role->hasCapability($required)) {
            return $this->problemDetails->create(
                $request,
                'forbidden',
                'Forbidden',
                403,
                'You do not have permission to perform this action.',
            );
        }

        // Organization scope check: skip for superadmin and routes where org is not resolved.
        // OrgResolverMiddleware sets nene2.org.id (an int) only for org-scoped routes.
        if ($role !== Role::Superadmin) {
            $resolvedOrgId = $request->getAttribute('nene2.org.id');

            if (is_int($resolvedOrgId)) {
                $jwtOrgId = isset($claims['org_id']) && is_int($claims['org_id'])
                    ? $claims['org_id']
                    : null;

                if ($jwtOrgId !== $resolvedOrgId) {
                    return $this->problemDetails->create(
                        $request,
                        'org-access-denied',
                        'Organization Access Denied',
                        403,
                        'Access to this organization is not permitted.',
                    );
                }
            }
        }

        return $handler->handle($request);
    }
}
