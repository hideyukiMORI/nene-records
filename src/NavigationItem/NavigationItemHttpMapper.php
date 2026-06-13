<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class NavigationItemHttpMapper
{
    /** @return array<string, mixed> */
    public static function toArray(NavigationItem $item): array
    {
        return [
            'id' => $item->id,
            'label' => $item->label,
            'url' => $item->url,
            'location' => $item->location,
            'menu_id' => $item->menuId,
            'display_order' => $item->displayOrder,
            'created_at' => $item->createdAt,
            'updated_at' => $item->updatedAt,
        ];
    }
}
