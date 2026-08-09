<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

use Nene2\Http\RequestScopedHolder;
use NeNeRecords\PublicRecord\RenderPublicHomeHandler;
use NeNeRecords\PublicRecord\RenderPublicTypeArchiveHandler;
use NeNeRecords\UrlRedirect\UrlRedirectResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Outer request handler for single-origin serving: runs the full NENE2
 * application pipeline, then applies the single-origin edge layers in order on a
 * 404 — first a per-record custom permalink (#651), then the per-org 301 redirect
 * map (migrated old WordPress URLs), then an entity type's archive listing (#877),
 * then the built SPA shell fallback for client-routed navigations.
 *
 * Custom permalinks run before the redirect map so a live record sitting at a path
 * wins over a stale 301 whose source equals that path.
 *
 * The type archive runs after both: a record parked at `/posts` and an admin-authored
 * 301 are explicit content decisions, while the archive is derived from a type slug,
 * so it may only answer a path nothing else claimed.
 *
 * Composing these as one DI-wired PSR-15 handler (rather than procedural code in
 * the front controller) keeps the ordering explicit and end-to-end testable, and
 * keeps the org context — resolved inside the pipeline — valid for the edge
 * lookups because the request-scoped org holder is read within the same request.
 */
final readonly class SingleOriginKernel implements RequestHandlerInterface
{
    /**
     * @param RequestScopedHolder<bool> $orgMissing raised by OrgResolverMiddleware when
     *        the request named an organization that does not exist (or is inactive)
     */
    public function __construct(
        private RequestHandlerInterface $application,
        private CustomPermalinkResolver $customPermalink,
        private UrlRedirectResolver $redirects,
        private RenderPublicTypeArchiveHandler $typeArchive,
        private RenderPublicHomeHandler $frontPage,
        private SpaShellFallback $shell,
        private RequestScopedHolder $orgMissing,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->application->handle($request);

        // Every layer below answers *for a tenant* — the permalink / 301 / archive
        // lookups are org-scoped, and the shell is a tenant's page. When org
        // resolution produced a definite negative, there is no tenant to answer for,
        // so the pipeline's 404 (or the 403 for an inactive org) is the final answer.
        //
        // Without this the shell turned that 404 into a 200 for `/` and for every
        // client-routed surface (`/login`, `/admin/...`, `/search`), so a decommissioned
        // host stayed "up" in HTML while `/api/*` correctly 404'd — the shape that
        // monitoring is least able to catch, and one that reappears every time an org
        // is retired, independently of whether its DNS record is removed (#1057).
        if ($this->orgMissing->isSet() && $this->orgMissing->get() === true) {
            return $response;
        }

        $response = $this->customPermalink->apply($request, $response);
        $response = $this->redirects->apply($request, $response);
        // Entity type archive SSR (#877): `/posts` etc. were SPA-only, so crawlers got
        // the shell instead of the listing.
        $response = $this->typeArchive->apply($request, $response);
        // Front-page SSR (#701) runs before the shell so a pinned record renders at `/`
        // (SpaShellFallback then honours the resulting text/html instead of the shell).
        $response = $this->frontPage->apply($request, $response);

        return $this->shell->apply($request, $response);
    }
}
