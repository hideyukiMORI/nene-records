import type { SystemConfigDto } from './api-types'
import type { SystemConfig, UpdateSystemConfigInput } from './model'

export function mapSystemConfigDtoToModel(dto: SystemConfigDto): SystemConfig {
  return {
    tenantResolutionMode: dto.tenant_resolution_mode,
    tenantOrgSlug: dto.tenant_org_slug,
    tenantBaseDomain: dto.tenant_base_domain,
  }
}

export function mapUpdateInputToDto(input: UpdateSystemConfigInput): Partial<SystemConfigDto> {
  return {
    ...(input.tenantResolutionMode !== undefined && {
      tenant_resolution_mode: input.tenantResolutionMode,
    }),
    ...(input.tenantOrgSlug !== undefined && { tenant_org_slug: input.tenantOrgSlug }),
    ...(input.tenantBaseDomain !== undefined && { tenant_base_domain: input.tenantBaseDomain }),
  }
}
