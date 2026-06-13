<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\NavigationItem;

use NeNeRecords\NavigationItem\NavigationItem;
use NeNeRecords\NavigationItem\NavigationItemNotFoundException;
use NeNeRecords\NavigationItem\NavigationItemRepositoryInterface;

final class InMemoryNavigationItemRepository implements NavigationItemRepositoryInterface
{
    /** @var array<int, NavigationItem> */
    private array $items = [];

    private int $nextId = 1;

    /** @return list<NavigationItem> */
    public function findAll(): array
    {
        $items = array_values($this->items);
        usort(
            $items,
            static fn (NavigationItem $a, NavigationItem $b): int =>
                $a->displayOrder <=> $b->displayOrder ?: ($a->id ?? 0) <=> ($b->id ?? 0),
        );

        return $items;
    }

    public function findById(int $id): ?NavigationItem
    {
        return $this->items[$id] ?? null;
    }

    public function save(NavigationItem $item): int
    {
        $id = $this->nextId++;
        $now = date('Y-m-d H:i:s');
        $this->items[$id] = new NavigationItem(
            id: $id,
            label: $item->label,
            url: $item->url,
            location: $item->location,
            displayOrder: $item->displayOrder,
            createdAt: $now,
            updatedAt: $now,
            menuId: $item->menuId,
        );

        return $id;
    }

    public function update(NavigationItem $item): void
    {
        $id = $item->id;

        if ($id === null || !isset($this->items[$id])) {
            throw new NavigationItemNotFoundException($id ?? 0);
        }

        $now = date('Y-m-d H:i:s');
        $this->items[$id] = new NavigationItem(
            id: $id,
            label: $item->label,
            url: $item->url,
            location: $item->location,
            displayOrder: $item->displayOrder,
            createdAt: $this->items[$id]->createdAt,
            updatedAt: $now,
            menuId: $item->menuId,
        );
    }

    public function delete(int $id): void
    {
        unset($this->items[$id]);
    }
}
