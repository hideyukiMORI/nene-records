<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

/**
 * Normalization and validation for a record's custom permalink (#651).
 *
 * A custom permalink is a free-text URL path that MAY contain "/" (e.g.
 * "/company/about/team"). When set it overrides the entity type's permalink
 * pattern as the record's canonical public URL; when null the per-type pattern
 * is used unchanged.
 *
 * This is the single source of truth for the canonical stored form, shared by
 * the write side (Create/Update validation) and the read side (incoming-path
 * matching in the public router), so a request path and a stored value compare
 * byte-for-byte after the same normalization.
 *
 * Canonical form: lower-cased, single leading slash, no trailing slash, no
 * empty ("//") segments; each segment is a kebab slug (matching the entity
 * `slug` rule) so the path carries no spaces, encoded bytes, or dot files.
 */
final class EntityPermalink
{
    /** Matches the `url_redirects.source_path` / `entities.permalink` column width. */
    public const MAX_LENGTH = 255;

    /** Validation error codes returned by {@see self::validate()}. */
    public const ERROR_INVALID = 'invalid';
    public const ERROR_RESERVED = 'reserved';
    public const ERROR_TOO_LONG = 'too_long';

    /**
     * First path segments that would shadow real (non-record) routes and must
     * never become a custom permalink. Anything under these is owned by the app
     * (API, admin SPA, asset/media serving, SEO files, legacy /view twin, feeds).
     *
     * @var list<string>
     */
    private const RESERVED_FIRST_SEGMENTS = [
        'api',
        'admin',
        'superadmin',
        'health',
        'view',
        'feed',
        'media',
        'assets',
        'sitemap.xml',
        'robots.txt',
    ];

    /** A single kebab slug segment, e.g. "about", "our-team", "p404". */
    private const SEGMENT = '[a-z0-9]+(?:-[a-z0-9]+)*';

    /**
     * Reduce any user/request input to the canonical stored form. Pure transform
     * (never throws); an empty / slash-only input collapses to '' (= "no custom
     * permalink"). Validation is a separate step ({@see self::validate()}).
     */
    public static function normalize(string $raw): string
    {
        $value = strtolower(trim($raw));

        if ($value === '') {
            return '';
        }

        // Collapse runs of slashes, then trim leading/trailing ones so we can
        // re-add exactly one leading slash and guarantee no trailing slash.
        $value = (string) preg_replace('#/+#', '/', $value);
        $value = trim($value, '/');

        if ($value === '') {
            return '';
        }

        return '/' . $value;
    }

    /**
     * Validate an already-normalized permalink. Returns an error code constant
     * (see ERROR_*) or null when the value is acceptable.
     */
    public static function validate(string $normalized): ?string
    {
        if ($normalized === '') {
            return self::ERROR_INVALID;
        }

        if (strlen($normalized) > self::MAX_LENGTH) {
            return self::ERROR_TOO_LONG;
        }

        if (preg_match('#^/' . self::SEGMENT . '(?:/' . self::SEGMENT . ')*$#', $normalized) !== 1) {
            return self::ERROR_INVALID;
        }

        if (self::isReserved($normalized)) {
            return self::ERROR_RESERVED;
        }

        return null;
    }

    /** Human-readable (English, API-facing) message for a {@see self::validate()} code. */
    public static function messageForError(string $code): string
    {
        return match ($code) {
            self::ERROR_TOO_LONG => 'Permalink must be at most ' . self::MAX_LENGTH . ' characters.',
            self::ERROR_RESERVED => 'Permalink uses a reserved path prefix (e.g. /api, /admin).',
            default => 'Permalink must be a "/"-separated path of lowercase letters, numbers and hyphens (e.g. /company/about/team).',
        };
    }

    /**
     * True when the first segment collides with an app-owned route prefix and
     * must be rejected (e.g. "/api/...", "/admin", "/feed.xml" → "feed*").
     */
    public static function isReserved(string $normalized): bool
    {
        $first = explode('/', ltrim($normalized, '/'))[0];

        if ($first === '') {
            return false;
        }

        if (in_array($first, self::RESERVED_FIRST_SEGMENTS, true)) {
            return true;
        }

        // "/feed*": any first segment beginning with "feed" (feeds, feed-rss…).
        return str_starts_with($first, 'feed');
    }
}
