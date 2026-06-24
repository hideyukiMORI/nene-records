<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

use NeNeRecords\UrlRedirect\UrlRedirectResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Outer request handler for single-origin serving: runs the full NENE2
 * application pipeline, then applies the single-origin edge layers in order on
 * a 404 — first the per-org 301 redirect map (migrated old WordPress URLs), then
 * the built SPA shell fallback for client-routed navigations.
 *
 * Composing these as one DI-wired PSR-15 handler (rather than procedural code in
 * the front controller) keeps the ordering explicit and end-to-end testable, and
 * keeps the org context — resolved inside the pipeline — valid for the redirect
 * lookup because the request-scoped org holder is read within the same request.
 */
final readonly class SingleOriginKernel implements RequestHandlerInterface
{
    public function __construct(
        private RequestHandlerInterface $application,
        private UrlRedirectResolver $redirects,
        private SpaShellFallback $shell,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->application->handle($request);
        $response = $this->redirects->apply($request, $response);

        return $this->shell->apply($request, $response);
    }
}
