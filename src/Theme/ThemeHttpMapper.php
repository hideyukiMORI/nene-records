<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

final readonly class ThemeHttpMapper
{
    /** @return array<string, mixed> */
    public static function toArray(Theme $theme): array
    {
        return [
            'theme_key' => $theme->themeKey,
            'name' => $theme->name,
            'version' => $theme->version,
            'manifest' => $theme->manifest,
            'created_at' => $theme->createdAt,
            'updated_at' => $theme->updatedAt,
        ];
    }
}
