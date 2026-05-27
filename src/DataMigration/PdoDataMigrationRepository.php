<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Low-level operations for migrating organization_id across all tenant-scoped tables.
 */
final readonly class PdoDataMigrationRepository implements DataMigrationRepositoryInterface
{
    /** Tables that carry organization_id and should be included in migration. */
    private const TABLES = [
        'entity_types',
        'entities',
        'field_defs',
        'text_fields',
        'int_fields',
        'enum_fields',
        'bool_fields',
        'datetime_fields',
        'tags',
        'media',
        'navigation_items',
        'webhooks',
        'setting_defs',
        'setting_values',
        'setting_revisions',
        'comments',
        'access_logs',
        'entity_revisions',
        'entity_preview_tokens',
    ];

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    /**
     * Returns the count of records with organization_id = 0 per table.
     *
     * @return array<string, int>
     */
    public function countUnassigned(): array
    {
        $counts = [];
        foreach (self::TABLES as $table) {
            $row = $this->query->fetchOne(
                "SELECT COUNT(*) AS cnt FROM `{$table}` WHERE organization_id = 0",
                [],
            );
            $counts[$table] = (int) ($row['cnt'] ?? 0);
        }

        return $counts;
    }

    /**
     * Reassigns all records with organization_id = 0 to the given org.
     * Returns the number of updated rows per table.
     *
     * @return array<string, int>
     */
    public function assignAll(int $targetOrgId): array
    {
        $updated = [];
        foreach (self::TABLES as $table) {
            $affected = $this->query->execute(
                "UPDATE `{$table}` SET organization_id = ? WHERE organization_id = 0",
                [$targetOrgId],
            );
            $updated[$table] = $affected;
        }

        return $updated;
    }
}
