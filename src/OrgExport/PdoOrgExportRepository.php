<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Reads all tenant-scoped data for a given organization.
 */
final readonly class PdoOrgExportRepository implements OrgExportRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function findAllEntityTypes(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM entity_types WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllEntities(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM entities WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllFieldDefs(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM field_defs WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllTextFields(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM text_fields WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllIntFields(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM int_fields WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllEnumFields(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM enum_fields WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllBoolFields(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM bool_fields WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllDatetimeFields(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM datetime_fields WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllTags(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM tags WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllEntityTags(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT et.* FROM entity_tags et
             JOIN entities e ON e.id = et.entity_id
             WHERE e.organization_id = ?
             ORDER BY et.entity_id, et.tag_id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllNavigationItems(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM navigation_items WHERE organization_id = ? ORDER BY display_order',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllSettingDefs(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM setting_defs WHERE organization_id = ? ORDER BY setting_key',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllSettingValues(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM setting_values WHERE organization_id = ? ORDER BY setting_key',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllMedia(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM media WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }
}
