<?php

declare(strict_types=1);

namespace NeNeRecords\UrlRedirect;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Single-origin 301 redirect layer: when the router 404s a GET, look up the
 * request path in the per-org redirect map (populated by WXR import) and issue
 * a 301 to the new permalink, preserving SEO equity from the old WordPress URLs.
 *
 * Runs before {@see \NeNeRecords\Http\SpaShellFallback} so a migrated old URL
 * redirects instead of falling through to the SPA shell. API / media / view /
 * asset paths keep their genuine 404.
 */
final readonly class UrlRedirectResolver
{
    private const PASSTHROUGH = '#^/(api|media|view|assets)(/|$)#';

    public function __construct(
        private UrlRedirectRepositoryInterface $redirects,
        private ResponseFactoryInterface $responseFactory,
        /** Sub-directory install prefix (`APP_BASE_PATH`); '' = served at root. */
        private string $basePath = '',
    ) {
    }

    public function apply(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($response->getStatusCode() !== 404) {
            return $response;
        }

        if (strtoupper($request->getMethod()) !== 'GET') {
            return $response;
        }

        $path = $this->normalize($request->getUri()->getPath());

        if ($path === '') {
            return $response;
        }

        if (preg_match(self::PASSTHROUGH, $path) === 1) {
            return $response;
        }

        // Best-effort layer: the redirect map is org-scoped, so on requests where no
        // organization was resolved (e.g. /admin and other non-tenant SPA paths) the
        // org holder is unset and the lookup throws. A failed lookup must never break
        // the request — fall through so the SPA shell fallback can serve it.
        try {
            $target = $this->redirects->findTargetBySource($path);
        } catch (\Throwable) {
            return $response;
        }

        if ($target === null || $target === $path) {
            return $response;
        }

        $location = $this->basePath === '' ? $target : $this->basePath . $target;

        return $this->responseFactory->createResponse(301)->withHeader('Location', $location);
    }

    /** Strip a single trailing slash so "/a/b/" and "/a/b" map to one source. */
    public function normalize(string $path): string
    {
        if ($path === '/' || $path === '') {
            return '';
        }

        return rtrim($path, '/');
    }
}
