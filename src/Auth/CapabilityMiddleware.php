<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Enforces role-based capabilities on authenticated API requests.
 *
 * Runs after AdminApiAuthMiddleware. Unauthenticated requests pass through unchanged.
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

        return $handler->handle($request);
    }
}
