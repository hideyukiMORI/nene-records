<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Does this request method ask for a public page's representation? (#1021)
 *
 * `GET` and `HEAD` do. RFC 9110 §9.3.2: HEAD is identical to GET except that the
 * server MUST NOT send a body — the *headers* (status, Content-Type, everything
 * else) are the same. So every edge layer that builds a public page has to run
 * for HEAD too; only the body is dropped, and that happens at the emitter.
 *
 * This exists because the same `!== 'GET'` guard was written independently in five
 * places ({@see CustomPermalinkResolver}, {@see SpaShellFallback},
 * {@see \NeNeRecords\PublicRecord\RenderPublicHomeHandler},
 * {@see \NeNeRecords\PublicRecord\RenderPublicTypeArchiveHandler},
 * {@see \NeNeRecords\UrlRedirect\UrlRedirectResolver}). Every one of them skipped
 * HEAD, so the whole public site answered HEAD wrongly: real pages 404'd, and the
 * site root fell through to the framework's API index — a **200 with
 * `application/json`** plus the API rate-limit headers, which is worse than a 404
 * because monitoring stays quiet while framework details leak. The app-level 301
 * redirect map was skipped too, so external backlink checkers saw 404 where a
 * browser sees a redirect.
 *
 * Keep the predicate in one place: five copies meant fixing one still left four.
 */
final class PublicPageMethod
{
    private function __construct()
    {
    }

    /**
     * True for the representation-reading methods (`GET` / `HEAD`).
     */
    public static function reads(ServerRequestInterface $request): bool
    {
        $method = strtoupper($request->getMethod());

        return $method === 'GET' || $method === 'HEAD';
    }
}
