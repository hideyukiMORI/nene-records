<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * Turns a permalink (or one of its segments) into a human display label — the
 * shared fallback when a record has no title field (#657). Keeps the public
 * detail title, breadcrumb, and child-list labels consistent ("about-us" →
 * "About Us") instead of an opaque "Record #<id>".
 */
final class PermalinkLabel
{
    /** "about-us" → "About Us"; falls back to the raw segment for non-kebab input. */
    public static function humanize(string $segment): string
    {
        $words = [];
        foreach (explode('-', $segment) as $part) {
            if ($part === '') {
                continue;
            }
            $words[] = mb_strtoupper(mb_substr($part, 0, 1)) . mb_substr($part, 1);
        }

        return $words === [] ? $segment : implode(' ', $words);
    }

    /** The last non-empty path segment of a permalink, or null when there is none. */
    public static function lastSegment(?string $permalink): ?string
    {
        if ($permalink === null || trim($permalink) === '') {
            return null;
        }

        $segments = array_values(array_filter(
            explode('/', $permalink),
            static fn (string $segment): bool => $segment !== '',
        ));

        return $segments === [] ? null : $segments[count($segments) - 1];
    }
}
