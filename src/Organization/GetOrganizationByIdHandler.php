<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetOrganizationByIdHandler implements RequestHandlerInterface
{
    public function __construct(
        private GetOrganizationByIdUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = (array) $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id         = (int) ($parameters['id'] ?? 0);

        $output = $this->useCase->execute(new GetOrganizationByIdInput(id: $id));

        return $this->response->create([
            'id'            => $output->id,
            'name'          => $output->name,
            'slug'          => $output->slug,
            'custom_domain' => $output->customDomain,
            'plan'          => $output->plan,
            'is_active'     => $output->isActive,
            'created_at'    => $output->createdAt,
            'updated_at'    => $output->updatedAt,
        ]);
    }
}
