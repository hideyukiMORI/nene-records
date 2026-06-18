<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

interface ThemeRepositoryInterface
{
    /** @return list<Theme> */
    public function findAll(): array;

    public function findByKey(string $themeKey): ?Theme;

    public function existsByKey(string $themeKey): bool;

    public function save(Theme $theme): int;

    public function update(Theme $theme): void;

    public function deleteByKey(string $themeKey): void;
}
