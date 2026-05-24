<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoEntityTagRepository implements EntityTagRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    /** @return list<EntityTagListItem> */
    public function findTagsByEntityId(int $entityId): array
    {
        $rows = $this->query->fetchAll(
            <<<'SQL'
                SELECT t.id, t.slug, t.name
                FROM entity_tags et
                INNER JOIN tags t ON t.id = et.tag_id
                WHERE et.entity_id = ?
                ORDER BY t.id ASC
                SQL,
            [$entityId],
        );

        return array_map(
            static fn (array $row) => new EntityTagListItem(
                id: (int) $row['id'],
                slug: (string) $row['slug'],
                name: (string) $row['name'],
            ),
            $rows,
        );
    }

    public function isAttached(int $entityId, int $tagId): bool
    {
        $row = $this->query->fetchOne(
            'SELECT id FROM entity_tags WHERE entity_id = ? AND tag_id = ?',
            [$entityId, $tagId],
        );

        return $row !== null;
    }

    public function attach(int $entityId, int $tagId): void
    {
        $this->query->execute(
            'INSERT INTO entity_tags (entity_id, tag_id) VALUES (?, ?)',
            [$entityId, $tagId],
        );
    }

    public function detach(int $entityId, int $tagId): void
    {
        $this->query->execute(
            'DELETE FROM entity_tags WHERE entity_id = ? AND tag_id = ?',
            [$entityId, $tagId],
        );
    }
}
