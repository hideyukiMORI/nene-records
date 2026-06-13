<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Menu;

use NeNeRecords\Menu\Menu;
use NeNeRecords\Menu\MenuRepositoryInterface;

final class InMemoryMenuRepository implements MenuRepositoryInterface
{
    /** @var array<int, Menu> */
    private array $menus = [];

    private int $nextId = 1;

    /** @return list<Menu> */
    public function findAll(): array
    {
        return array_values($this->menus);
    }

    public function findById(int $id): ?Menu
    {
        return $this->menus[$id] ?? null;
    }

    public function existsBySlug(string $slug, ?int $excludeId = null): bool
    {
        foreach ($this->menus as $menu) {
            if ($menu->slug === $slug && $menu->id !== $excludeId) {
                return true;
            }
        }

        return false;
    }

    public function save(Menu $menu): int
    {
        $id = $this->nextId++;
        $now = date('Y-m-d H:i:s');
        $this->menus[$id] = new Menu(
            id: $id,
            name: $menu->name,
            slug: $menu->slug,
            location: $menu->location,
            createdAt: $now,
            updatedAt: $now,
        );

        return $id;
    }

    public function update(Menu $menu): void
    {
        $id = $menu->id;
        if ($id === null || !isset($this->menus[$id])) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->menus[$id] = new Menu(
            id: $id,
            name: $menu->name,
            slug: $menu->slug,
            location: $menu->location,
            createdAt: $this->menus[$id]->createdAt,
            updatedAt: $now,
        );
    }

    public function delete(int $id): void
    {
        unset($this->menus[$id]);
    }
}
