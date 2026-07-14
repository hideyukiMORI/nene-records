<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

use Nene2\Http\ClockInterface;

/**
 * Builds the tenant-scoped export payload for one organization.
 *
 * Single source of truth for the payload shape, shared by the HTTP
 * {@see ExportOrganizationHandler} and the CLI tools/export-org.php so a new
 * table is only ever added in one place.
 */
final readonly class OrgExportPayloadBuilder
{
    public function __construct(
        private OrgExportRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /** @return array<string, mixed> */
    public function build(int $orgId): array
    {
        return [
            'meta'                  => [
                'exported_at'     => $this->clock->now()->format('c'),
                'organization_id' => $orgId,
            ],
            'entity_types'          => $this->repository->findAllEntityTypes($orgId),
            'entities'              => $this->repository->findAllEntities($orgId),
            'field_defs'            => $this->repository->findAllFieldDefs($orgId),
            'text_fields'           => $this->repository->findAllTextFields($orgId),
            'int_fields'            => $this->repository->findAllIntFields($orgId),
            'enum_fields'           => $this->repository->findAllEnumFields($orgId),
            'bool_fields'           => $this->repository->findAllBoolFields($orgId),
            'datetime_fields'       => $this->repository->findAllDatetimeFields($orgId),
            'tags'                  => $this->repository->findAllTags($orgId),
            'entity_tags'           => $this->repository->findAllEntityTags($orgId),
            'navigation_items'      => $this->repository->findAllNavigationItems($orgId),
            'setting_defs'          => $this->repository->findAllSettingDefs($orgId),
            'setting_values'        => $this->repository->findAllSettingValues($orgId),
            'media'                 => $this->repository->findAllMedia($orgId),
            'menus'                 => $this->repository->findAllMenus($orgId),
            'widgets'               => $this->repository->findAllWidgets($orgId),
            'themes'                => $this->repository->findAllThemes($orgId),
            'blocks_fields'         => $this->repository->findAllBlocksFields($orgId),
            'entity_relations'      => $this->repository->findAllEntityRelations($orgId),
            'url_redirects'         => $this->repository->findAllUrlRedirects($orgId),
            'comments'              => $this->repository->findAllComments($orgId),
            'webhooks'              => $this->repository->findAllWebhooks($orgId),
            'webhook_deliveries'    => $this->repository->findAllWebhookDeliveries($orgId),
            'notification_channels' => $this->repository->findAllNotificationChannels($orgId),
            'user_profiles'         => $this->repository->findAllUserProfiles($orgId),
        ];
    }
}
