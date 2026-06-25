<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Organization\OrganizationIterator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ProcessScheduledPublishHandler
{
    public function __construct(
        private ProcessScheduledPublishUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        // Prod wires this so the cron covers every tenant; null = current org only
        // (the request-resolved org, e.g. single-org deploys / focused tests).
        private ?OrganizationIterator $organizations = null,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // The use case is org-scoped (findDueScheduled reads the org holder), so run
        // it once per active tenant. In subdomain mode the org-less cron cannot
        // resolve a tenant, so the iterator drives the per-org scoping.
        $published = [];

        if ($this->organizations === null) {
            $published = $this->useCase->execute()->publishedIds;
        } else {
            $this->organizations->forEachActive(function () use (&$published): void {
                $published = array_merge($published, $this->useCase->execute()->publishedIds);
            });
        }

        return $this->response->create([
            'published_count' => count($published),
            'published_ids' => $published,
        ]);
    }
}
