<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoThemeRepository implements ThemeRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private RequestScopedHolder $orgId,
        private ClockInterface $clock,
    ) {
    }

    /** @return list<Theme> */
    public function findAll(): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, theme_key, name, version, manifest, created_at, updated_at
             FROM themes
             WHERE organization_id = ?
             ORDER BY name ASC',
            [$this->orgId->get()],
        );

        return array_map($this->mapRow(...), $rows);
    }

    public function findByKey(string $themeKey): ?Theme
    {
        $row = $this->query->fetchOne(
            'SELECT id, theme_key, name, version, manifest, created_at, updated_at
             FROM themes
             WHERE theme_key = ? AND organization_id = ?',
            [$themeKey, $this->orgId->get()],
        );

        return $row === null ? null : $this->mapRow($row);
    }

    public function existsByKey(string $themeKey): bool
    {
        $row = $this->query->fetchOne(
            'SELECT id FROM themes WHERE theme_key = ? AND organization_id = ?',
            [$themeKey, $this->orgId->get()],
        );

        return $row !== null;
    }

    public function save(Theme $theme): int
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->query->execute(
            'INSERT INTO themes (organization_id, theme_key, name, version, source, manifest, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $this->orgId->get(),
                $theme->themeKey,
                $theme->name,
                $theme->version,
                'runtime',
                self::encode($theme->manifest),
                $now,
                $now,
            ],
        );

        return $this->query->lastInsertId();
    }

    public function update(Theme $theme): void
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->query->execute(
            'UPDATE themes
             SET name = ?, version = ?, manifest = ?, updated_at = ?
             WHERE theme_key = ? AND organization_id = ?',
            [
                $theme->name,
                $theme->version,
                self::encode($theme->manifest),
                $now,
                $theme->themeKey,
                $this->orgId->get(),
            ],
        );
    }

    public function deleteByKey(string $themeKey): void
    {
        $this->query->execute(
            'DELETE FROM themes WHERE theme_key = ? AND organization_id = ?',
            [$themeKey, $this->orgId->get()],
        );
    }

    /** @param array<string, mixed> $manifest */
    private static function encode(array $manifest): string
    {
        return json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): Theme
    {
        $decoded = json_decode((string) $row['manifest'], true);
        /** @var array<string, mixed> $manifest */
        $manifest = is_array($decoded) ? $decoded : [];

        return new Theme(
            id: (int) $row['id'],
            themeKey: (string) $row['theme_key'],
            name: (string) $row['name'],
            version: (string) $row['version'],
            manifest: $manifest,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
