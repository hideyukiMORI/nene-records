<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

final readonly class MenuHttpMapper
{
    /** @return array<string, mixed> */
    public static function toArray(Menu $menu): array
    {
        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'slug' => $menu->slug,
            'location' => $menu->location,
            'created_at' => $menu->createdAt,
            'updated_at' => $menu->updatedAt,
        ];
    }
}
