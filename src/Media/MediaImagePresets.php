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
