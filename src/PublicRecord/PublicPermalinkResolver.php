<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Resolves a public record permalink from an entity type's pattern.
 *
 * Mirrors the frontend resolver (`frontend/src/shared/lib/resolve-permalink.ts`)
 * so server-rendered canonical/og:url values match the SPA's user-facing URLs.
 *
 * Supported tokens: {type} {slug} {id} {year} {month} {day}
 * ({year}/{month}/{day} come from publishedAt in UTC, else "0000"/"00"/"00").
 */
final readonly class PublicPermalinkResolver
{
    public const DEFAULT_PATTERN = '/{type}/{id}';

    public static function resolve(
        ?string $pattern,
        string $typeSlug,
        ?string $entitySlug,
        int $entityId,
        ?DateTimeImmutable $publishedAt,
    ): string {
        $pat = ($pattern === null || $pattern === '') ? self::DEFAULT_PATTERN : $pattern;

        if ($publishedAt !== null) {
            $utc = $publishedAt->setTimezone(new DateTimeZone('UTC'));
            $year = $utc->format('Y');
            $month = $utc->format('m');
            $day = $utc->format('d');
        } else {
            $year = '0000';
            $month = '00';
            $day = '00';
        }

        $slug = ($entitySlug === null || $entitySlug === '') ? (string) $entityId : $entitySlug;

        return strtr($pat, [
            '{type}' => $typeSlug,
            '{slug}' => $slug,
            '{id}' => (string) $entityId,
            '{year}' => $year,
            '{month}' => $month,
            '{day}' => $day,
        ]);
    }

    /**
     * Reverse-resolve a permalink's trailing path (everything after the type slug)
     * into the entity lookup key. Mirrors the frontend `extractEntityKeyFromSplat`:
     * an id pattern (`{id}` without `{slug}`) resolves the last numeric segment as
     * an id; otherwise the last path segment is the slug.
     *
     * @return array{entityId: int|null, entitySlug: string|null}
     */
    public static function extractEntityKey(?string $pattern, string $splat): array
    {
        $pat = ($pattern === null || $pattern === '') ? self::DEFAULT_PATTERN : $pattern;

        $segments = array_values(array_filter(
            explode('/', $splat),
            static fn (string $segment): bool => $segment !== '',
        ));
        $last = $segments === [] ? $splat : $segments[count($segments) - 1];

        if (str_contains($pat, '{id}') && !str_contains($pat, '{slug}') && ctype_digit($last)) {
            return ['entityId' => (int) $last, 'entitySlug' => null];
        }

        return ['entityId' => null, 'entitySlug' => $last];
    }
}
