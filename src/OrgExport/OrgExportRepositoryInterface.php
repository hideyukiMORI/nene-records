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

    /** @return list<array<string, mixed>> */
    public function findAllComments(int $orgId): array;

    /**
     * Webhook config rows. The HMAC signing `secret` column is intentionally
     * omitted from the payload — it is re-provisioned on the target (#836).
     *
     * @return list<array<string, mixed>>
     */
    public function findAllWebhooks(int $orgId): array;

    /**
     * Webhook delivery-queue rows, scoped through the parent webhook. The
     * per-delivery `secret` snapshot is omitted (see findAllWebhooks).
     *
     * @return list<array<string, mixed>>
     */
    public function findAllWebhookDeliveries(int $orgId): array;

    /** @return list<array<string, mixed>> */
    public function findAllNotificationChannels(int $orgId): array;

    /**
     * User profiles, scoped through the owning user. Each row carries the
     * owner's `user_email` so the target can re-attach it to a same-email user
     * (users themselves are never exported — see PdoOrgImportRepository).
     *
     * @return list<array<string, mixed>>
     */
    public function findAllUserProfiles(int $orgId): array;
}
