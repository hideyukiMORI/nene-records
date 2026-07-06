<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoMenuRepository implements MenuRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private readonly RequestScopedHolder $orgId,
        private ClockInterface $clock,
    ) {
    }

    /** @return list<Menu> */
    public function findAll(): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, name, slug, location, created_at, updated_at
             FROM menus
             WHERE organization_id = ?
             ORDER BY id ASC',
            [$this->orgId->get()],
        );

        return array_map($this->mapRow(...), $rows);
    }

    public function findById(int $id): ?Menu
    {
        $row = $this->query->fetchOne(
            'SELECT id, name, slug, location, created_at, updated_at
             FROM menus
             WHERE id = ? AND organization_id = ?',
            [$id, $this->orgId->get()],
        );

        return $row === null ? null : $this->mapRow($row);
    }

    public function existsBySlug(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $row = $this->query->fetchOne(
                'SELECT id FROM menus WHERE slug = ? AND organization_id = ? AND id != ?',
                [$slug, $this->orgId->get(), $excludeId],
            );
        } else {
            $row = $this->query->fetchOne(
                'SELECT id FROM menus WHERE slug = ? AND organization_id = ?',
                [$slug, $this->orgId->get()],
            );
        }

        return $row !== null;
    }

    public function save(Menu $menu): int
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->query->execute(
            'INSERT INTO menus (organization_id, name, slug, location, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$this->orgId->get(), $menu->name, $menu->slug, $menu->location, $now, $now],
        );

        return $this->query->lastInsertId();
    }

    public function update(Menu $menu): void
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->query->execute(
            'UPDATE menus
             SET name = ?, slug = ?, location = ?, updated_at = ?
             WHERE id = ? AND organization_id = ?',
            [$menu->name, $menu->slug, $menu->location, $now, $menu->id, $this->orgId->get()],
        );
    }

    public function delete(int $id): void
    {
        $this->query->execute('DELETE FROM menus WHERE id = ? AND organization_id = ?', [$id, $this->orgId->get()]);
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): Menu
    {
        $location = $row['location'];

        return new Menu(
            id: (int) $row['id'],
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            location: $location === null ? null : (string) $location,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
