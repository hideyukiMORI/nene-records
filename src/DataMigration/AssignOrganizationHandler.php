<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Reassigns all records with organization_id = 0 to the specified organization.
 * This is the "single → multi" data migration operation.
 */
final readonly class AssignOrganizationHandler implements RequestHandlerInterface
{
    public function __construct(
        private AssignOrganizationUseCaseInterface $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body  = JsonRequestBodyParser::parse($request);
        $orgId = isset($body['target_org_id']) ? (int) $body['target_org_id'] : 0;

        $input  = new AssignOrganizationInput(targetOrgId: $orgId);
        $output = $this->useCase->execute($input);

        return $this->json->create([
            'organization_id'   => $output->organizationId,
            'organization_name' => $output->organizationName,
            'total'             => $output->total,
            'tables'            => $output->tables,
        ]);
    }
}
