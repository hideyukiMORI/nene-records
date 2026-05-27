<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

use DateTimeImmutable;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Exports all tenant-scoped data for a given organization as a JSON payload.
 *
 * GET /api/v1/superadmin/organizations/{id}/export
 */
final readonly class OrgExportHandler implements RequestHandlerInterface
{
    public function __construct(
        private OrgExportRepository $repository,
        private OrganizationRepositoryInterface $orgs,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id    = (int) ($request->getAttribute('id') ?? 0);
        $org   = $this->orgs->findById($id);

        if ($org === null) {
            return $this->problemDetails->create(
                $request,
                'org-not-found',
                'Organization Not Found',
                404,
                "No organization found with id {$id}.",
            );
        }

        $now = (new DateTimeImmutable('now'))->format('c');

        $payload = [
            'meta'              => [
                'exported_at'     => $now,
                'organization_id' => $id,
            ],
            'entity_types'      => $this->repository->fetchEntityTypes($id),
            'entities'          => $this->repository->fetchEntities($id),
            'field_defs'        => $this->repository->fetchFieldDefs($id),
            'text_fields'       => $this->repository->fetchTextFields($id),
            'int_fields'        => $this->repository->fetchIntFields($id),
            'enum_fields'       => $this->repository->fetchEnumFields($id),
            'bool_fields'       => $this->repository->fetchBoolFields($id),
            'datetime_fields'   => $this->repository->fetchDatetimeFields($id),
            'tags'              => $this->repository->fetchTags($id),
            'entity_tags'       => $this->repository->fetchEntityTags($id),
            'navigation_items'  => $this->repository->fetchNavigationItems($id),
            'setting_defs'      => $this->repository->fetchSettingDefs($id),
            'setting_values'    => $this->repository->fetchSettingValues($id),
            'media'             => $this->repository->fetchMedia($id),
        ];

        return $this->json->create($payload);
    }
}
