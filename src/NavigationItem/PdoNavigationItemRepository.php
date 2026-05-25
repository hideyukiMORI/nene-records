<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoNavigationItemRepository implements NavigationItemRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    /** @return list<NavigationItem> */
    public function findAll(): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, label, url, display_order, created_at, updated_at
             FROM navigation_items
             ORDER BY display_order ASC, id ASC',
        );

        return array_map($this->mapRow(...), $rows);
    }

    public function findById(int $id): ?NavigationItem
    {
        $row = $this->query->fetchOne(
            'SELECT id, label, url, display_order, created_at, updated_at
             FROM navigation_items
             WHERE id = ?',
            [$id],
        );

        return $row === null ? null : $this->mapRow($row);
    }

    public function save(NavigationItem $item): int
    {
        $now = date('Y-m-d H:i:s');

        $this->query->execute(
            'INSERT INTO navigation_items (label, url, display_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?)',
            [$item->label, $item->url, $item->displayOrder, $now, $now],
        );

        $row = $this->query->fetchOne('SELECT LAST_INSERT_ID() AS id');

        return isset($row['id']) ? (int) $row['id'] : 0;
    }

    public function update(NavigationItem $item): void
    {
        $now = date('Y-m-d H:i:s');

        $this->query->execute(
            'UPDATE navigation_items
             SET label = ?, url = ?, display_order = ?, updated_at = ?
             WHERE id = ?',
            [$item->label, $item->url, $item->displayOrder, $now, $item->id],
        );
    }

    public function delete(int $id): void
    {
        $this->query->execute('DELETE FROM navigation_items WHERE id = ?', [$id]);
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): NavigationItem
    {
        return new NavigationItem(
            id: (int) $row['id'],
            label: (string) $row['label'],
            url: (string) $row['url'],
            displayOrder: (int) $row['display_order'],
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
