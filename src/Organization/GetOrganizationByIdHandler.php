<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetOrganizationByIdHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $repository,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = (array) $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);
        $org = $this->repository->findById($id);

        if ($org === null) {
            throw new OrganizationNotFoundException($id);
        }

        return $this->response->create([
            'id' => $org->id,
            'name' => $org->name,
            'slug' => $org->slug,
            'custom_domain' => $org->customDomain,
            'plan' => $org->plan,
            'is_active' => $org->isActive,
            'created_at' => $org->createdAt,
            'updated_at' => $org->updatedAt,
        ]);
    }
}
