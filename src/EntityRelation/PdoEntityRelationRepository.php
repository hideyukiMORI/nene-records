<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoEntityRelationRepository implements EntityRelationRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    /** @return list<ListEntityRelationItem> */
    public function findByEntityId(int $entityId): array
    {
        $rows = $this->query->fetchAll(
            <<<'SQL'
                SELECT field_key, target_entity_id
                FROM entity_relations
                WHERE source_entity_id = ?
                ORDER BY field_key ASC, target_entity_id ASC
                SQL,
            [$entityId],
        );

        return array_map(
            static fn (array $row) => new ListEntityRelationItem(
                fieldKey: (string) $row['field_key'],
                targetEntityId: (int) $row['target_entity_id'],
            ),
            $rows,
        );
    }

    /** @return list<ListEntityRelationItem> */
    public function findByEntityIdAndFieldKey(int $entityId, string $fieldKey): array
    {
        $rows = $this->query->fetchAll(
            <<<'SQL'
                SELECT field_key, target_entity_id
                FROM entity_relations
                WHERE source_entity_id = ? AND field_key = ?
                ORDER BY target_entity_id ASC
                SQL,
            [$entityId, $fieldKey],
        );

        return array_map(
            static fn (array $row) => new ListEntityRelationItem(
                fieldKey: (string) $row['field_key'],
                targetEntityId: (int) $row['target_entity_id'],
            ),
            $rows,
        );
    }

    public function isAttached(int $sourceEntityId, int $targetEntityId, string $fieldKey): bool
    {
        $row = $this->query->fetchOne(
            <<<'SQL'
                SELECT id
                FROM entity_relations
                WHERE source_entity_id = ? AND target_entity_id = ? AND field_key = ?
                SQL,
            [$sourceEntityId, $targetEntityId, $fieldKey],
        );

        return $row !== null;
    }

    public function attach(int $sourceEntityId, int $targetEntityId, string $fieldKey): void
    {
        $this->query->execute(
            'INSERT INTO entity_relations (source_entity_id, target_entity_id, field_key) VALUES (?, ?, ?)',
            [$sourceEntityId, $targetEntityId, $fieldKey],
        );
    }

    public function detach(int $sourceEntityId, int $targetEntityId, string $fieldKey): void
    {
        $this->query->execute(
            <<<'SQL'
                DELETE FROM entity_relations
                WHERE source_entity_id = ? AND target_entity_id = ? AND field_key = ?
                SQL,
            [$sourceEntityId, $targetEntityId, $fieldKey],
        );
    }

    public function detachAllForFieldKey(int $sourceEntityId, string $fieldKey): void
    {
        $this->query->execute(
            'DELETE FROM entity_relations WHERE source_entity_id = ? AND field_key = ?',
            [$sourceEntityId, $fieldKey],
        );
    }
}
