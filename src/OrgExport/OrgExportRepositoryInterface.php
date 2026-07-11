<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

interface OrgExportRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function findAllEntityTypes(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllEntities(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllFieldDefs(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllTextFields(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllIntFields(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllEnumFields(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllBoolFields(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllDatetimeFields(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllTags(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllEntityTags(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllNavigationItems(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllSettingDefs(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllSettingValues(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllMedia(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllMenus(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllWidgets(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllThemes(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllBlocksFields(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllEntityRelations(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllUrlRedirects(int $orgId): array;
}
