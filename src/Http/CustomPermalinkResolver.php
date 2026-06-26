<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Single-origin edge layer for per-record custom permalinks (#651): when the
 * router 404s a GET, look the request path up against the per-org custom-permalink
 * map and, if a record claims it, serve that record's crawlable view.
 *
 * Runs BEFORE {@see \NeNeRecords\UrlRedirect\UrlRedirectResolver} so a live record
 * sitting at a path always wins over a stale 301 whose source equals that path,
 * and before {@see SpaShellFallback} so the deep custom path is served rather than
 * falling through to the SPA shell.
 *
 * Because it acts only on an already-404 response, fixed routes (type/slug/date/id
 * permalinks, /api, /view, sitemap, robots) are never disturbed. API / media /
 * view / asset / admin paths keep their genuine 404.
 */
final readonly class CustomPermalinkResolver
{
    private const PASSTHROUGH = '#^/(api|media|view|assets|admin|superadmin)(/|$)#';

    public function __construct(
        private PublicPermalinkRendererInterface $renderer,
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

        $path = $request->getUri()->getPath();

        if ($path === '' || $path === '/') {
            return $response;
        }

        if (preg_match(self::PASSTHROUGH, $path) === 1) {
            return $response;
        }

        // Best-effort, org-scoped lookup: on requests where no organization was
        // resolved (e.g. apex/admin SPA paths) the org holder is unset and the
        // lookup throws — a failure must never break the request, so fall through.
        try {
            $rendered = $this->renderer->renderByPermalink($path, $request);
        } catch (\Throwable) {
            return $response;
        }

        return $rendered ?? $response;
    }
}
