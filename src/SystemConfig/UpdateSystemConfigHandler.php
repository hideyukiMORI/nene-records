<?php

declare(strict_types=1);

namespace NeNeRecords\SystemConfig;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UpdateSystemConfigHandler implements RequestHandlerInterface
{
    public function __construct(
        private UpdateSystemConfigUseCaseInterface $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $input = new UpdateSystemConfigInput(
            tenantResolutionMode: isset($body['tenant_resolution_mode'])
                ? (string) $body['tenant_resolution_mode']
                : '',
            tenantOrgSlug: isset($body['tenant_org_slug'])
                ? (string) $body['tenant_org_slug']
                : null,
            tenantBaseDomain: isset($body['tenant_base_domain'])
                ? (string) $body['tenant_base_domain']
                : null,
        );

        $output = $this->useCase->execute($input);

        return $this->json->create([
            'tenant_resolution_mode' => $output->tenantResolutionMode,
            'tenant_org_slug'        => $output->tenantOrgSlug,
            'tenant_base_domain'     => $output->tenantBaseDomain,
        ]);
    }
}
