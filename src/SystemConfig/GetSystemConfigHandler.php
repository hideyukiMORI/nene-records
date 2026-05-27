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
        private SystemConfigRepository $config,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $all = $this->config->all();

        return $this->json->create([
            'tenant_resolution_mode' => $all['tenant_resolution_mode'] ?? 'single',
            'tenant_org_slug'        => $all['tenant_org_slug'] ?? '',
            'tenant_base_domain'     => $all['tenant_base_domain'] ?? 'localhost',
        ]);
    }
}
