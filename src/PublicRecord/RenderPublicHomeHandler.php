<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\Http\AcceptPrefersHtml;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Single-origin edge layer for the public site root `/` (#701).
 *
 * When the org pinned a `front_page` that resolves to a currently published record on a
 * tenant host, this server-renders that record as the home page (canonical = site root,
 * og:type = website, no breadcrumbs) and returns it in place of the framework-info JSON.
 * Otherwise it passes the response through unchanged, so API clients keep the `/` payload
 * and a browser gets the default magazine home / SaaS landing via the SPA-shell fallback.
 *
 * It is an edge layer (not a route) because the framework already owns a `/` route that a
 * later app route cannot beat (equal specificity → registration order wins). Running after
 * the application, like {@see \NeNeRecords\Http\SpaShellFallback}, keeps the resolved org
 * context valid and lets it override the framework's `/` answer for the home navigation.
 */
final readonly class RenderPublicHomeHandler
{
    public function __construct(
        private FrontPageSetting $frontPage,
        private PublicRecordViewRendererInterface $renderer,
    ) {
    }

    public function apply(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Only a browser navigation to the exact site root is a candidate.
        if (strtoupper($request->getMethod()) !== 'GET' || $request->getUri()->getPath() !== '/') {
            return $response;
        }

        // The catch-all wildcard (plain curl, SNS unfurlers) and a missing header count as wanting
        // HTML (#915) — only an explicit non-HTML Accept keeps the framework's
        // JSON index at `/`.
        if (!AcceptPrefersHtml::check($request->getHeaderLine('Accept'))) {
            return $response;
        }

        // Only the framework's 200 info payload (non-HTML) may be taken over: a throttled
        // 429 or a temporary 5xx must keep its status instead of being masked by a fresh
        // 200 SSR (`/` would otherwise bypass rate limiting), and an upstream HTML page
        // is already the real answer.
        if ($response->getStatusCode() !== 200
            || str_contains($response->getHeaderLine('Content-Type'), 'text/html')) {
            return $response;
        }

        $front = $this->frontPage->resolvePublished();

        if ($front === null) {
            return $response;
        }

        [$entity, $type] = $front;

        try {
            return $this->renderer->renderEntity($type->slug, null, (int) $entity->id, $request, asFrontPage: true);
        } catch (Throwable) {
            // Never white-screen the home page: fall back to the default home on any failure.
            return $response;
        }
    }
}
