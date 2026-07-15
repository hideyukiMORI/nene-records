<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Single-origin edge layer that server-renders an entity type's archive at
 * `/{typeSlug}` (#877).
 *
 * The SPA already routes `:entityTypeSlug` to `PublicBrowsePage`, but the server
 * had no such route, so the request fell through to {@see \NeNeRecords\Http\SpaShellFallback}
 * and crawlers got a 1.6KB shell titled "NeNe Records Admin" instead of the listing.
 * This renders the same list server-side; the SPA still replaces it on mount.
 *
 * Placed after {@see \NeNeRecords\Http\CustomPermalinkResolver} and the redirect map
 * in {@see \NeNeRecords\Http\SingleOriginKernel}: a real record parked at `/posts`
 * and an admin-authored 301 are both explicit, and must beat this derived listing.
 * Acting only on an already-404 response keeps every fixed route (/api, /view,
 * sitemap, robots, assets) untouched.
 */
final readonly class RenderPublicTypeArchiveHandler
{
    /** Same reserved prefixes CustomPermalinkResolver passes through. */
    private const PASSTHROUGH = '#^/(api|media|view|assets|admin|superadmin)(/|$)#';

    public function __construct(
        private GetPublicTypeArchiveUseCase $useCase,
        private PublicTypeArchiveRendererInterface $renderer,
    ) {
    }

    public function apply(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($response->getStatusCode() !== 404 || strtoupper($request->getMethod()) !== 'GET') {
            return $response;
        }

        if (!str_contains($request->getHeaderLine('Accept'), 'text/html')) {
            return $response;
        }

        $path = $request->getUri()->getPath();

        if (preg_match(self::PASSTHROUGH, $path) === 1) {
            return $response;
        }

        $slug = $this->singleSegment($path);

        if ($slug === null) {
            return $response;
        }

        try {
            $archive = $this->useCase->execute($slug, $this->offset($request));
        } catch (Throwable) {
            // An archive is a nice-to-have surface: on any failure keep the original
            // 404 so the shell fallback still answers, rather than white-screening.
            return $response;
        }

        if ($archive === null) {
            return $response;
        }

        try {
            return $this->renderer->render($archive, $request);
        } catch (Throwable) {
            return $response;
        }
    }

    /** "/posts" → "posts"; anything deeper, empty or dotted (files) → null. */
    private function singleSegment(string $path): ?string
    {
        $trimmed = trim($path, '/');

        if ($trimmed === '' || str_contains($trimmed, '/') || str_contains($trimmed, '.')) {
            return null;
        }

        return rawurldecode($trimmed);
    }

    private function offset(ServerRequestInterface $request): int
    {
        $params = $request->getQueryParams();
        $raw = $params['offset'] ?? null;

        if (!is_string($raw) || !ctype_digit($raw)) {
            return 0;
        }

        return (int) $raw;
    }
}
