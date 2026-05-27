<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListOrganizationsHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $repository,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $limit = max(1, min(100, (int) ($params['limit'] ?? 20)));
        $offset = max(0, (int) ($params['offset'] ?? 0));

        $organizations = $this->repository->findAll($limit, $offset);
        $total = $this->repository->count();

        return $this->response->create([
            'data' => array_map(static fn (Organization $o) => self::toArray($o), $organizations),
            'meta' => ['total' => $total, 'limit' => $limit, 'offset' => $offset],
        ]);
    }

    /** @return array<string, mixed> */
    private static function toArray(Organization $o): array
    {
        return [
            'id' => $o->id,
            'name' => $o->name,
            'slug' => $o->slug,
            'custom_domain' => $o->customDomain,
            'plan' => $o->plan,
            'is_active' => $o->isActive,
            'created_at' => $o->createdAt,
            'updated_at' => $o->updatedAt,
        ];
    }
}
