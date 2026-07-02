<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

use NeNeRecords\PublicRecord\RenderPublicHomeHandler;
use NeNeRecords\UrlRedirect\UrlRedirectResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Outer request handler for single-origin serving: runs the full NENE2
 * application pipeline, then applies the single-origin edge layers in order on a
 * 404 — first a per-record custom permalink (#651), then the per-org 301 redirect
 * map (migrated old WordPress URLs), then the built SPA shell fallback for
 * client-routed navigations.
 *
 * Custom permalinks run before the redirect map so a live record sitting at a path
 * wins over a stale 301 whose source equals that path.
 *
 * Composing these as one DI-wired PSR-15 handler (rather than procedural code in
 * the front controller) keeps the ordering explicit and end-to-end testable, and
 * keeps the org context — resolved inside the pipeline — valid for the edge
 * lookups because the request-scoped org holder is read within the same request.
 */
final readonly class SingleOriginKernel implements RequestHandlerInterface
{
    public function __construct(
        private RequestHandlerInterface $application,
        private CustomPermalinkResolver $customPermalink,
        private UrlRedirectResolver $redirects,
        private RenderPublicHomeHandler $frontPage,
        private SpaShellFallback $shell,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->application->handle($request);
        $response = $this->customPermalink->apply($request, $response);
        $response = $this->redirects->apply($request, $response);
        // Front-page SSR (#701) runs before the shell so a pinned record renders at `/`
        // (SpaShellFallback then honours the resulting text/html instead of the shell).
        $response = $this->frontPage->apply($request, $response);

        return $this->shell->apply($request, $response);
    }
}
