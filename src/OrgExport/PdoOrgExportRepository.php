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

    /** @return list<array<string, mixed>> */
    public function findAllMenus(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM menus WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllWidgets(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM widgets WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllThemes(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM themes WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllBlocksFields(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM blocks_fields WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /**
     * entity_relations has no organization_id column — scope through the source entity.
     *
     * @return list<array<string, mixed>>
     */
    public function findAllEntityRelations(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT er.* FROM entity_relations er
             JOIN entities e ON e.id = er.source_entity_id
             WHERE e.organization_id = ?
             ORDER BY er.id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllUrlRedirects(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM url_redirects WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllComments(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM comments WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /**
     * The `secret` column is deliberately excluded — HMAC secrets are not moved
     * between instances (re-provisioned on the target, #836).
     *
     * @return list<array<string, mixed>>
     */
    public function findAllWebhooks(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT id, organization_id, url, events, entity_type_id, is_active, created_at, updated_at
             FROM webhooks WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /**
     * webhook_deliveries has no organization_id — scope through the parent webhook.
     * The per-delivery `secret` snapshot is excluded like findAllWebhooks.
     *
     * @return list<array<string, mixed>>
     */
    public function findAllWebhookDeliveries(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT d.id, d.webhook_id, d.event, d.entity_type_id, d.entity_id, d.target_url,
                    d.payload, d.status, d.attempts, d.max_attempts, d.next_attempt_at,
                    d.last_error, d.response_status, d.created_at, d.updated_at, d.delivered_at
             FROM webhook_deliveries d
             JOIN webhooks w ON w.id = d.webhook_id
             WHERE w.organization_id = ?
             ORDER BY d.id',
            [$orgId],
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllNotificationChannels(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT * FROM notification_channels WHERE organization_id = ? ORDER BY id',
            [$orgId],
        );
    }

    /**
     * user_profiles has no organization_id — scope through the owning user and
     * carry the user's email so the target can re-attach the profile by email.
     *
     * @return list<array<string, mixed>>
     */
    public function findAllUserProfiles(int $orgId): array
    {
        return $this->query->fetchAll(
            'SELECT p.id, p.user_id, p.display_name, p.full_name, p.job_title,
                    p.created_at, p.updated_at, u.email AS user_email
             FROM user_profiles p
             JOIN users u ON u.id = p.user_id
             WHERE u.organization_id = ?
             ORDER BY p.id',
            [$orgId],
        );
    }
}
