<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

/**
 * Resizes and re-encodes raster images for on-demand derivative generation.
 * Kept behind an interface so the GD implementation can be swapped for Imagick
 * or an external service (imgproxy/Thumbor) without touching the HTTP layer.
 */
interface ImageProcessorInterface
{
    public const FORMAT_WEBP = 'webp';
    public const FORMAT_AVIF = 'avif';
    public const FORMAT_JPEG = 'jpeg';
    public const FORMAT_PNG = 'png';

    /** Whether the given source MIME type can be processed. */
    public function supportsSource(string $mimeType): bool;

    /**
     * Resize $sourceBytes to fit within $maxWidth (never upscaling) and encode as
     * $format (one of the FORMAT_* constants). Returns the encoded image bytes.
     *
     * @throws \RuntimeException when the source cannot be decoded or encoded.
     */
    public function resize(string $sourceBytes, int $maxWidth, string $format): string;
}
