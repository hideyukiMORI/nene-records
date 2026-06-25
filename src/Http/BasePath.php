<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

/**
 * Public base path support (#540-adjacent / zip-install foundation): lets the
 * app be served from a sub-directory (e.g. `https://example.com/blog/`) like
 * WordPress, set explicitly via the `APP_BASE_PATH` env var.
 *
 * Only URL *generation* is base-aware (canonical / permalink / og:url / sitemap /
 * redirect / asset URLs); request routing stays root-relative because the web
 * server strips the prefix (Caddy `handle_path` / Apache rewrite). Default `''`
 * = root, so the whole thing is a no-op until configured.
 */
final class BasePath
{
    private function __construct()
    {
    }

    /** The configured base path from `APP_BASE_PATH`, normalized. */
    public static function fromEnv(): string
    {
        $raw = getenv('APP_BASE_PATH');

        return self::normalize($raw === false ? null : $raw);
    }

    /** Normalize to `''` (root) or `/segment` (leading slash, no trailing slash). */
    public static function normalize(?string $raw): string
    {
        $trimmed = trim((string) $raw);
        $trimmed = trim($trimmed, '/');

        return $trimmed === '' ? '' : '/' . $trimmed;
    }

    /**
     * Prefix a root-relative path (starting with `/`) with the base. A base of
     * `''` returns the path unchanged; the site root `/` becomes `{base}/`.
     */
    public static function prefix(string $base, string $path): string
    {
        if ($base === '') {
            return $path;
        }

        return $path === '/' ? $base . '/' : $base . $path;
    }
}
