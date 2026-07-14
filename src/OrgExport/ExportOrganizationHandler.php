<?php

declare(strict_types=1);

namespace NeNeRecords\OrgExport;

use Nene2\Error\ProblemDetailsResponseFactory;
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
 *
 * This endpoint returns DB rows only. Media originals are transported by the CLI
 * zip flow (tools/export-org.php → tools/import-org.php, #798), which reuses the
 * same {@see OrgExportPayloadBuilder} for the DB half.
 */
final readonly class ExportOrganizationHandler implements RequestHandlerInterface
{
    public function __construct(
        private OrgExportPayloadBuilder $payloadBuilder,
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

        return $this->json->create($this->payloadBuilder->build($id));
    }
}
