<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Imports an org-export payload into the target organization.
 *
 * POST /api/v1/superadmin/organizations/{id}/import
 *
 * The request body must be a JSON export payload produced by ExportOrganizationHandler.
 * All IDs are remapped to new auto-increment values; existing data in the
 * target organization is not removed before import.
 */
final readonly class ImportOrganizationHandler implements RequestHandlerInterface
{
    public function __construct(
        private OrgImportRepositoryInterface $repository,
        private OrganizationRepositoryInterface $orgs,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
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

        $payload = JsonRequestBodyParser::parse($request);

        if (!isset($payload['meta'])) {
            return $this->problemDetails->create(
                $request,
                'invalid-payload',
                'Invalid Import Payload',
                422,
                'Request body must be a valid org-export JSON payload (must contain "meta" key).',
            );
        }

        $counts = $this->repository->import($id, $payload);
        $total  = array_sum($counts);

        return $this->json->create([
            'organization_id'   => $id,
            'organization_name' => $org->name,
            'total'             => $total,
            'imported'          => $counts,
        ], 201);
    }
}
