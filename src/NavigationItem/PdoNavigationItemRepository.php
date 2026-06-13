<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoNavigationItemRepository implements NavigationItemRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private readonly RequestScopedHolder $orgId,
    ) {
    }

    /** @return list<NavigationItem> */
    public function findAll(): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, label, url, location, display_order, created_at, updated_at
             FROM navigation_items
             WHERE organization_id = ?
             ORDER BY display_order ASC, id ASC',
            [$this->orgId->get()],
        );

        return array_map($this->mapRow(...), $rows);
    }

    public function findById(int $id): ?NavigationItem
    {
        $row = $this->query->fetchOne(
            'SELECT id, label, url, location, display_order, created_at, updated_at
             FROM navigation_items
             WHERE id = ? AND organization_id = ?',
            [$id, $this->orgId->get()],
        );

        return $row === null ? null : $this->mapRow($row);
    }

    public function save(NavigationItem $item): int
    {
        $now = date('Y-m-d H:i:s');

        $this->query->execute(
            'INSERT INTO navigation_items (organization_id, label, url, location, display_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$this->orgId->get(), $item->label, $item->url, $item->location, $item->displayOrder, $now, $now],
        );

        return $this->query->lastInsertId();
    }

    public function update(NavigationItem $item): void
    {
        $now = date('Y-m-d H:i:s');

        $this->query->execute(
            'UPDATE navigation_items
             SET label = ?, url = ?, location = ?, display_order = ?, updated_at = ?
             WHERE id = ? AND organization_id = ?',
            [$item->label, $item->url, $item->location, $item->displayOrder, $now, $item->id, $this->orgId->get()],
        );
    }

    public function delete(int $id): void
    {
        $this->query->execute('DELETE FROM navigation_items WHERE id = ? AND organization_id = ?', [$id, $this->orgId->get()]);
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): NavigationItem
    {
        return new NavigationItem(
            id: (int) $row['id'],
            label: (string) $row['label'],
            url: (string) $row['url'],
            location: (string) $row['location'],
            displayOrder: (int) $row['display_order'],
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
