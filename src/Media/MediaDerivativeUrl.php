<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

/**
 * Resolves the on-demand derivative URL for a media-library image URL.
 *
 * Mirrors the frontend resolver (`frontend/src/shared/lib/media-derivatives.ts`):
 * given the public URL of a media image (`.../media/{year}/{month}/{file}`),
 * returns the derivative URL for a preset (`.../media/{preset}/{year}/{month}/{file}`).
 * Returns null for non-media URLs, non-image extensions, or unknown presets so
 * callers can fall back (e.g. omit og:image).
 */
final readonly class MediaDerivativeUrl
{
    private const MEDIA_PATH = '~^(.*/media/)(\d{4})/(\d{2})/([^/?#]+)$~';
    private const IMAGE_EXTENSIONS = '/\.(png|jpe?g|webp|gif|avif)$/i';

    public static function forPreset(string $url, string $preset): ?string
    {
        if (!MediaImagePresets::isValid($preset)) {
            return null;
        }

        if (preg_match(self::MEDIA_PATH, $url, $m) !== 1) {
            return null;
        }

        [, $prefix, $year, $month, $filename] = $m;

        if (preg_match(self::IMAGE_EXTENSIONS, $filename) !== 1) {
            return null;
        }

        return $prefix . $preset . '/' . $year . '/' . $month . '/' . $filename;
    }
}
