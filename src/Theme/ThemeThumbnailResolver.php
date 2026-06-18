<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use NeNeRecords\Media\MediaRepositoryInterface;

/**
 * Resolves a runtime theme's `assets.preview` media id into a URL for the
 * picker thumbnail (#426 A案). The manifest stores a media id (uploaded via the
 * Media API); we resolve it to a URL here, like `logo_media_id` in settings.
 * Returns '' when there is no preview, an unresolved id, or a non-media value
 * (bundle paths cannot be hosted by runtime themes).
 */
final readonly class ThemeThumbnailResolver
{
    public function __construct(
        private MediaRepositoryInterface $media,
    ) {
    }

    /** @param array<string, mixed> $manifest */
    public function resolve(array $manifest): string
    {
        $assets = $manifest['assets'] ?? null;
        if (!is_array($assets)) {
            return '';
        }

        $id = self::extractMediaId($assets['preview'] ?? null);
        if ($id === null) {
            return '';
        }

        $media = $this->media->findById($id);

        return $media === null ? '' : $media->url;
    }

    /** A media id is a positive int, or the light/dark int of a per-mode object. */
    private static function extractMediaId(mixed $preview): ?int
    {
        if (is_int($preview)) {
            return $preview > 0 ? $preview : null;
        }
        if (is_array($preview)) {
            foreach (['light', 'dark'] as $mode) {
                $value = $preview[$mode] ?? null;
                if (is_int($value) && $value > 0) {
                    return $value;
                }
            }
        }

        return null;
    }
}
