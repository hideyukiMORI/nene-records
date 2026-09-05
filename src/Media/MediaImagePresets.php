<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

/**
 * Whitelist of named derivative sizes. Restricting derivatives to a fixed set
 * of presets prevents arbitrary-size generation from being used as a DoS / disk
 * exhaustion vector.
 */
final class MediaImagePresets
{
    /** @var array<string, int> preset name => max width in pixels */
    private const PRESETS = [
        'thumb' => 160,
        'sm' => 320,
        'md' => 640,
        'lg' => 1280,
        // Social card width (og:image / twitter:image). Aspect is preserved
        // (width-constrained), which social platforms crop/letterbox as needed.
        'og' => 1200,
        // Favicon sizes (#1073). Named rather than free-form for the same reason as the rest
        // of this list: the closed set is what stops arbitrary-size generation being used as a
        // disk/CPU DoS. 180 is the apple-touch-icon size; 192 covers the web app manifest.
        'icon32' => 32,
        'icon180' => 180,
        'icon192' => 192,
    ];

    public static function isValid(string $name): bool
    {
        return isset(self::PRESETS[$name]);
    }

    public static function maxWidth(string $name): int
    {
        return self::PRESETS[$name] ?? throw new \InvalidArgumentException('Unknown image preset: ' . $name);
    }

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::PRESETS);
    }
}
