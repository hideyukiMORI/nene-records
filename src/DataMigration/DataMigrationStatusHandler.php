<?php

declare(strict_types=1);

namespace NeNeRecords\DataMigration;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Returns the count of records with organization_id = 0 per table.
 * Used to show the user how many records would be affected by an org assignment.
 */
final readonly class DataMigrationStatusHandler implements RequestHandlerInterface
{
    public function __construct(
        private DataMigrationRepository $repository,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $counts = $this->repository->countUnassigned();
        $total  = array_sum($counts);

        return $this->json->create([
            'total'  => $total,
            'tables' => $counts,
        ]);
    }
}
