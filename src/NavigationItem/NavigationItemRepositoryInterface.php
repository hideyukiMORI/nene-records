<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

interface NavigationItemRepositoryInterface
{
    /** @return list<NavigationItem> */
    public function findAll(): array;

    public function findById(int $id): ?NavigationItem;

    public function save(NavigationItem $item): int;

    public function update(NavigationItem $item): void;

    public function delete(int $id): void;
}
