<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

final readonly class WidgetHttpMapper
{
    /** @return array<string, mixed> */
    public static function toArray(Widget $widget): array
    {
        return [
            'id' => $widget->id,
            'widget_type' => $widget->widgetType,
            'region' => $widget->region,
            'display_order' => $widget->displayOrder,
            'title' => $widget->title,
            'settings' => $widget->settings === [] ? new \stdClass() : $widget->settings,
            'created_at' => $widget->createdAt,
            'updated_at' => $widget->updatedAt,
        ];
    }
}
