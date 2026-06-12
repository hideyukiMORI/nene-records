<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

interface WidgetRepositoryInterface
{
    /** @return list<Widget> */
    public function findAll(): array;

    public function findById(int $id): ?Widget;

    public function save(Widget $widget): int;

    public function update(Widget $widget): void;

    public function delete(int $id): void;
}
