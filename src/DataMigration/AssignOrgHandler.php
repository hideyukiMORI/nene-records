<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Organization\OrganizationRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Reassigns all records with organization_id = 0 to the specified organization.
 * This is the "single → multi" data migration operation.
 */
final readonly class AssignOrgHandler implements RequestHandlerInterface
{
    public function __construct(
        private DataMigrationRepository $repository,
        private OrganizationRepositoryInterface $orgs,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body  = JsonRequestBodyParser::parse($request);
        $orgId = isset($body['target_org_id']) ? (int) $body['target_org_id'] : 0;

        if ($orgId <= 0) {
            return $this->problemDetails->create(
                $request,
                'validation-failed',
                'Validation Failed',
                422,
                'target_org_id must be a positive integer.',
            );
        }

        $org = $this->orgs->findById($orgId);
        if ($org === null) {
            return $this->problemDetails->create(
                $request,
                'org-not-found',
                'Organization Not Found',
                404,
                "No organization found with id {$orgId}.",
            );
        }

        $migrated = $this->repository->assignAll($orgId);
        $total    = array_sum($migrated);

        return $this->json->create([
            'organization_id'   => $orgId,
            'organization_name' => $org->name,
            'total'             => $total,
            'tables'            => $migrated,
        ]);
    }
}
