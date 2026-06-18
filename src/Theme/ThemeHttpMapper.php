<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

final readonly class ThemeHttpMapper
{
    /** @return array<string, mixed> */
    public static function toArray(Theme $theme, string $thumbnailUrl = ''): array
    {
        return [
            'theme_key' => $theme->themeKey,
            'name' => $theme->name,
            'version' => $theme->version,
            'manifest' => $theme->manifest,
            'thumbnail_url' => $thumbnailUrl,
            'created_at' => $theme->createdAt,
            'updated_at' => $theme->updatedAt,
        ];
    }
}
