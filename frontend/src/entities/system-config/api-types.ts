export interface SystemConfigDto {
  tenant_resolution_mode: 'single' | 'subdomain' | 'path'
  tenant_org_slug: string
  tenant_base_domain: string
}
