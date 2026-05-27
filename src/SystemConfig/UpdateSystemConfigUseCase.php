<?php

declare(strict_types=1);

namespace NeNeRecords\SystemConfig;

use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;

final readonly class UpdateSystemConfigUseCase implements UpdateSystemConfigUseCaseInterface
{
    private const VALID_MODES = ['single', 'subdomain', 'path'];

    public function __construct(
        private SystemConfigRepositoryInterface $config,
    ) {
    }

    public function execute(UpdateSystemConfigInput $input): UpdateSystemConfigOutput
    {
        if (!in_array($input->tenantResolutionMode, self::VALID_MODES, true)) {
            throw new ValidationException([
                new ValidationError(
                    'tenant_resolution_mode',
                    'tenant_resolution_mode must be one of: ' . implode(', ', self::VALID_MODES) . '.',
                    'invalid',
                ),
            ]);
        }

        $this->config->set('tenant_resolution_mode', $input->tenantResolutionMode);

        if ($input->tenantOrgSlug !== null) {
            $this->config->set('tenant_org_slug', $input->tenantOrgSlug);
        }

        if ($input->tenantBaseDomain !== null) {
            $this->config->set('tenant_base_domain', $input->tenantBaseDomain);
        }

        $all = $this->config->all();

        return new UpdateSystemConfigOutput(
            tenantResolutionMode: $all['tenant_resolution_mode'] ?? 'single',
            tenantOrgSlug: $all['tenant_org_slug'] ?? '',
            tenantBaseDomain: $all['tenant_base_domain'] ?? 'localhost',
        );
    }
}
