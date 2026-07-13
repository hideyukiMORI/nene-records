<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

use Nene2\Auth\TokenVerificationException;
use Nene2\Auth\TokenVerifierInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Method-aware API authentication middleware.
 *
 * Protection rules (first match wins):
 *  1. Always open: /health, /api/v1/auth/*, /api/v1/public/*, and the
 *     cron-triggered batch endpoints (process-scheduled / process-deliveries)
 *  2. Always protected (all HTTP methods incl. OPTIONS): the ADMIN_ONLY_PREFIXES
 *     (includes sensitive reads: webhooks / notification-channels / export / dashboard)
 *  3. Protected for non-GET methods: all other /api/v1/ paths. GET/HEAD are the
 *     public content-read surface (the consumer site reads them unauthenticated).
 *  4. Everything else: open (static assets, public HTML pages)
 */
final readonly class AdminApiAuthMiddleware implements MiddlewareInterface
{
    /**
     * Open prefixes. The two `process-*` entries are full paths (not true
     * prefixes) for cron-triggered batch jobs: the cron container drains them
     * over HTTP without credentials. They expose no data, are idempotent, and
     * remain rate-limited by ThrottleMiddleware (#466).
     *
     * @var list<string>
     */
    private const ALWAYS_OPEN_PREFIXES = [
        '/health',
        '/api/v1/auth/',
        '/api/v1/public/',
        '/api/v1/entities/process-scheduled',
        '/api/v1/webhooks/process-deliveries',
    ];

    /** @var list<string> */
    private const ADMIN_ONLY_PREFIXES = [
        '/api/v1/settings',
        '/api/v1/navigation-items',
        '/api/v1/menus',
        '/api/v1/widgets',
        '/api/v1/media',
        '/api/v1/users',
        '/api/v1/admin/comments',
        '/api/v1/organizations',
        // Superadmin console (export/import, data-migration, system-config). Must
        // be authenticated for ALL methods — the GET export is not a "non-GET"
        // mutation, so without this prefix its read of every tenant's data would
        // be unauthenticated. Role is then enforced by CapabilityMiddleware. See #797.
        '/api/v1/superadmin/',
        '/api/v1/themes',
        '/api/v1/migration',
        '/api/v1/account',
        // Sensitive reads that must never be anonymous, even though the rest of the
        // content API (entity-types / entities / text-fields / tags / analytics-
        // popular) stays readable for the public consumer site. Webhooks expose
        // signing secrets, notification-channels expose delivery configs, export is
        // a bulk dump, dashboard is org business metrics. None are used by the public
        // site. See security assessment #824 and public-site regression #826.
        // (`/api/v1/webhooks/process-deliveries` stays open via ALWAYS_OPEN above,
        // which is matched first.)
        '/api/v1/webhooks',
        '/api/v1/notification-channels',
        '/api/v1/entities/export',
        '/api/v1/dashboard',
    ];

    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
        private TokenVerifierInterface $verifier,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Whether this route ENFORCES auth (401 without a valid token). Open routes
        // still authenticate *opportunistically*: if a valid token is present we
        // attach the claims so downstream handlers (e.g. the open public content-read
        // surface) can distinguish an authenticated admin from an anonymous visitor
        // and widen what they return (drafts, all statuses). See #828.
        $requiresAuth = $this->requiresAuthentication($request);

        $authorization = $request->getHeaderLine('Authorization');
        $credentialType = 'bearer';

        if ($authorization !== '') {
            if (!str_starts_with($authorization, 'Bearer ')) {
                if ($requiresAuth) {
                    return $this->unauthorized($request, 'invalid_token', 'Authorization header must use the Bearer scheme.');
                }

                return $handler->handle($request);
            }

            $token = substr($authorization, 7);
        } else {
            // Fall back to the HttpOnly session cookie (browser SPA).
            $cookies = $request->getCookieParams();
            $cookieToken = isset($cookies[SessionCookie::NAME]) && is_string($cookies[SessionCookie::NAME])
                ? $cookies[SessionCookie::NAME]
                : '';

            if ($cookieToken === '') {
                if ($requiresAuth) {
                    return $this->unauthorized($request, 'missing_token', 'No session token was provided.');
                }

                return $handler->handle($request);
            }

            $token = $cookieToken;
            $credentialType = 'cookie';

            // CSRF: the cookie is sent automatically by the browser, so a
            // cross-site form could trigger a state-changing request. Require a
            // custom header that only same-origin JS sets — a cross-site form
            // post cannot set it without a CORS preflight the API does not allow.
            $method = strtoupper($request->getMethod());
            if (
                in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)
                && $request->getHeaderLine('X-Requested-With') === ''
            ) {
                return $this->forbiddenCsrf($request);
            }
        }

        try {
            $claims = $this->verifier->verify($token);
        } catch (TokenVerificationException $e) {
            if ($requiresAuth) {
                return $this->unauthorized($request, 'invalid_token', $e->getMessage());
            }

            // Open route with an invalid/expired token → serve as anonymous.
            return $handler->handle($request);
        }

        return $handler->handle(
            $request
                ->withAttribute('nene2.auth.credential_type', $credentialType)
                ->withAttribute('nene2.auth.claims', $claims),
        );
    }

    private function forbiddenCsrf(ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create(
            $request,
            'csrf-protection',
            'Forbidden',
            403,
            'Cookie-authenticated requests must include the X-Requested-With header.',
        );
    }

    private function requiresAuthentication(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath() ?: '/';
        $method = strtoupper($request->getMethod());

        // 1. Always open paths
        foreach (self::ALWAYS_OPEN_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        // 2. Admin-only prefixes: protect for all methods
        foreach (self::ADMIN_ONLY_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        // 3. All other /api/v1/* paths: protect non-GET methods. GET/HEAD are the
        //    public content-read surface the consumer site depends on (entity-types,
        //    entities, text-fields, tags, analytics-popular — all read unauthenticated
        //    to render public pages). Genuinely sensitive GETs are enumerated in
        //    ADMIN_ONLY_PREFIXES above. See #824 (secret/export lockdown) and #826
        //    (public-site regression: over-broad GET lockdown broke the consumer site).
        //    KNOWN RESIDUAL: anonymous content reads can still surface unpublished
        //    records via `?status` — to be tightened to published-only for anonymous
        //    callers (#826 follow-up), not by blanket-protecting the read surface.
        if (str_starts_with($path, '/api/v1/')) {
            return $method !== 'GET' && $method !== 'HEAD' && $method !== 'OPTIONS';
        }

        // 4. Everything else: open (HTML pages, static assets)
        return false;
    }

    private function unauthorized(ServerRequestInterface $request, string $error, string $description): ResponseInterface
    {
        return $this->problemDetails
            ->create($request, 'unauthorized', 'Unauthorized', 401, $description)
            ->withHeader(
                'WWW-Authenticate',
                sprintf(
                    'Bearer realm="NeNe Records", error="%s", error_description="%s"',
                    $error,
                    $this->sanitizeHeaderParam($description),
                ),
            );
    }

    private function sanitizeHeaderParam(string $value): string
    {
        return str_replace('"', '\\"', preg_replace('/\r?\n|\r/', ' ', $value) ?? $value);
    }
}
