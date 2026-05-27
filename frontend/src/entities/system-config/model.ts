export type TenantResolutionMode = 'single' | 'subdomain' | 'path'

export interface SystemConfig {
  tenantResolutionMode: TenantResolutionMode
  tenantOrgSlug: string
  tenantBaseDomain: string
}

export interface UpdateSystemConfigInput {
  tenantResolutionMode?: TenantResolutionMode
  tenantOrgSlug?: string
  tenantBaseDomain?: string
}
