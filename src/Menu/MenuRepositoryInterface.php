<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

interface MenuRepositoryInterface
{
    /** @return list<Menu> */
    public function findAll(): array;

    public function findById(int $id): ?Menu;

    public function existsBySlug(string $slug, ?int $excludeId = null): bool;

    public function save(Menu $menu): int;

    public function update(Menu $menu): void;

    public function delete(int $id): void;
}
