<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Widget;

use NeNeRecords\Widget\Widget;
use NeNeRecords\Widget\WidgetRepositoryInterface;

final class InMemoryWidgetRepository implements WidgetRepositoryInterface
{
    /** @var array<int, Widget> */
    private array $items = [];

    private int $nextId = 1;

    /** @return list<Widget> */
    public function findAll(): array
    {
        $items = array_values($this->items);
        usort(
            $items,
            static fn (Widget $a, Widget $b): int =>
                [$a->region, $a->displayOrder, $a->id ?? 0] <=> [$b->region, $b->displayOrder, $b->id ?? 0],
        );

        return $items;
    }

    public function findById(int $id): ?Widget
    {
        return $this->items[$id] ?? null;
    }

    public function save(Widget $widget): int
    {
        $id = $this->nextId++;
        $now = date('Y-m-d H:i:s');
        $this->items[$id] = new Widget(
            id: $id,
            widgetType: $widget->widgetType,
            region: $widget->region,
            displayOrder: $widget->displayOrder,
            title: $widget->title,
            settings: $widget->settings,
            createdAt: $now,
            updatedAt: $now,
        );

        return $id;
    }

    public function update(Widget $widget): void
    {
        $id = $widget->id;
        if ($id === null || !isset($this->items[$id])) {
            return;
        }

        $this->items[$id] = new Widget(
            id: $id,
            widgetType: $widget->widgetType,
            region: $widget->region,
            displayOrder: $widget->displayOrder,
            title: $widget->title,
            settings: $widget->settings,
            createdAt: $this->items[$id]->createdAt,
            updatedAt: date('Y-m-d H:i:s'),
        );
    }

    public function delete(int $id): void
    {
        unset($this->items[$id]);
    }
}
