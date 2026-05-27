<?php

declare(strict_types=1);

namespace NeNeRecords\SystemConfig;

final readonly class GetSystemConfigUseCase implements GetSystemConfigUseCaseInterface
{
    public function __construct(
        private SystemConfigRepositoryInterface $config,
    ) {
    }

    public function execute(): GetSystemConfigOutput
    {
        $all = $this->config->all();

        return new GetSystemConfigOutput(
            tenantResolutionMode: $all['tenant_resolution_mode'] ?? 'single',
            tenantOrgSlug: $all['tenant_org_slug'] ?? '',
            tenantBaseDomain: $all['tenant_base_domain'] ?? 'localhost',
        );
    }
}
