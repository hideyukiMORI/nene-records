<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use RuntimeException;

/**
 * GD-backed {@see ImageProcessorInterface}. Preserves alpha and never upscales.
 */
final readonly class GdImageProcessor implements ImageProcessorInterface
{
    private const SUPPORTED_SOURCES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/avif',
    ];

    public function __construct(
        private int $quality = 80,
    ) {
    }

    public function supportsSource(string $mimeType): bool
    {
        return in_array($mimeType, self::SUPPORTED_SOURCES, true);
    }

    public function resize(string $sourceBytes, int $maxWidth, string $format): string
    {
        $src = @imagecreatefromstring($sourceBytes);

        if ($src === false) {
            throw new RuntimeException('Failed to decode source image.');
        }

        try {
            $srcWidth = imagesx($src);
            $srcHeight = imagesy($src);

            // Never upscale: cap the target width at the source width.
            $targetWidth = max(1, min($maxWidth, $srcWidth));
            $targetHeight = max(1, (int) round($srcHeight * $targetWidth / $srcWidth));

            $dst = imagecreatetruecolor($targetWidth, $targetHeight);

            if ($dst === false) {
                throw new RuntimeException('Failed to allocate destination image.');
            }

            try {
                // Preserve transparency for PNG/WebP/AVIF output.
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                if ($transparent !== false) {
                    imagefill($dst, 0, 0, $transparent);
                }

                imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, $srcWidth, $srcHeight);

                return $this->encode($dst, $format);
            } finally {
                imagedestroy($dst);
            }
        } finally {
            imagedestroy($src);
        }
    }

    private function encode(\GdImage $image, string $format): string
    {
        ob_start();

        $ok = match ($format) {
            self::FORMAT_WEBP => imagewebp($image, null, $this->quality),
            self::FORMAT_AVIF => imageavif($image, null, $this->quality),
            self::FORMAT_JPEG => imagejpeg($image, null, $this->quality),
            self::FORMAT_PNG => imagepng($image),
            default => throw new RuntimeException('Unsupported output format: ' . $format),
        };

        $bytes = ob_get_clean();

        if (!$ok || $bytes === false || $bytes === '') {
            throw new RuntimeException('Failed to encode image as ' . $format . '.');
        }

        return $bytes;
    }
}
