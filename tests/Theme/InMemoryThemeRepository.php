<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Theme;

use NeNeRecords\Theme\Theme;
use NeNeRecords\Theme\ThemeRepositoryInterface;

final class InMemoryThemeRepository implements ThemeRepositoryInterface
{
    /** @var array<string, Theme> keyed by themeKey */
    private array $themes = [];

    private int $nextId = 1;

    /** @return list<Theme> */
    public function findAll(): array
    {
        return array_values($this->themes);
    }

    public function findByKey(string $themeKey): ?Theme
    {
        return $this->themes[$themeKey] ?? null;
    }

    public function existsByKey(string $themeKey): bool
    {
        return isset($this->themes[$themeKey]);
    }

    public function save(Theme $theme): int
    {
        $id = $this->nextId++;
        $now = date('Y-m-d H:i:s');
        $this->themes[$theme->themeKey] = new Theme(
            id: $id,
            themeKey: $theme->themeKey,
            name: $theme->name,
            version: $theme->version,
            manifest: $theme->manifest,
            createdAt: $now,
            updatedAt: $now,
        );

        return $id;
    }

    public function update(Theme $theme): void
    {
        if (!isset($this->themes[$theme->themeKey])) {
            return;
        }
        $existing = $this->themes[$theme->themeKey];
        $this->themes[$theme->themeKey] = new Theme(
            id: $existing->id,
            themeKey: $theme->themeKey,
            name: $theme->name,
            version: $theme->version,
            manifest: $theme->manifest,
            createdAt: $existing->createdAt,
            updatedAt: date('Y-m-d H:i:s'),
        );
    }

    public function deleteByKey(string $themeKey): void
    {
        unset($this->themes[$themeKey]);
    }
}
