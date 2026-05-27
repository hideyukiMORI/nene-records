<?php

declare(strict_types=1);

namespace NeNeRecords\SystemConfig;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetSystemConfigHandler implements RequestHandlerInterface
{
    public function __construct(
        private GetSystemConfigUseCaseInterface $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        return $this->json->create([
            'tenant_resolution_mode' => $output->tenantResolutionMode,
            'tenant_org_slug'        => $output->tenantOrgSlug,
            'tenant_base_domain'     => $output->tenantBaseDomain,
        ]);
    }
}
