<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Exports all tenant-scoped data for a given organization as a JSON payload.
 *
 * GET /api/v1/superadmin/organizations/{id}/export
 */
final readonly class ExportOrganizationHandler implements RequestHandlerInterface
{
    public function __construct(
        private OrgExportRepositoryInterface $repository,
        private OrganizationRepositoryInterface $orgs,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
        private ClockInterface $clock,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id  = (int) Router::param($request, 'id');
        $org = $this->orgs->findById($id);

        if ($org === null) {
            return $this->problemDetails->create(
                $request,
                'org-not-found',
                'Organization Not Found',
                404,
                "No organization found with id {$id}.",
            );
        }

        $now = $this->clock->now()->format('c');

        $payload = [
            'meta'             => [
                'exported_at'     => $now,
                'organization_id' => $id,
            ],
            'entity_types'     => $this->repository->findAllEntityTypes($id),
            'entities'         => $this->repository->findAllEntities($id),
            'field_defs'       => $this->repository->findAllFieldDefs($id),
            'text_fields'      => $this->repository->findAllTextFields($id),
            'int_fields'       => $this->repository->findAllIntFields($id),
            'enum_fields'      => $this->repository->findAllEnumFields($id),
            'bool_fields'      => $this->repository->findAllBoolFields($id),
            'datetime_fields'  => $this->repository->findAllDatetimeFields($id),
            'tags'             => $this->repository->findAllTags($id),
            'entity_tags'      => $this->repository->findAllEntityTags($id),
            'navigation_items' => $this->repository->findAllNavigationItems($id),
            'setting_defs'     => $this->repository->findAllSettingDefs($id),
            'setting_values'   => $this->repository->findAllSettingValues($id),
            'media'            => $this->repository->findAllMedia($id),
            'menus'            => $this->repository->findAllMenus($id),
            'widgets'          => $this->repository->findAllWidgets($id),
            'themes'           => $this->repository->findAllThemes($id),
            'blocks_fields'    => $this->repository->findAllBlocksFields($id),
            'entity_relations' => $this->repository->findAllEntityRelations($id),
            'url_redirects'    => $this->repository->findAllUrlRedirects($id),
            'comments'         => $this->repository->findAllComments($id),
            'webhooks'         => $this->repository->findAllWebhooks($id),
            'webhook_deliveries' => $this->repository->findAllWebhookDeliveries($id),
            'notification_channels' => $this->repository->findAllNotificationChannels($id),
            'user_profiles'    => $this->repository->findAllUserProfiles($id),
        ];

        return $this->json->create($payload);
    }
}
