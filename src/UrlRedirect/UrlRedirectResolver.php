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

        $target = $this->redirects->findTargetBySource($path);

        if ($target === null || $target === $path) {
            return $response;
        }

        return $this->responseFactory->createResponse(301)->withHeader('Location', $target);
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
