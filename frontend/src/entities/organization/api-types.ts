export interface OrganizationDto {
  id: number
  name: string
  slug: string
  custom_domain: string | null
  plan: string
  is_active: boolean
  created_at: string | null
  updated_at: string | null
}

export interface OrganizationListDto {
  data: OrganizationDto[]
  meta: {
    total: number
    limit: number
    offset: number
  }
}

export interface CreateOrganizationDto {
  name: string
  slug: string
  plan: string
  custom_domain?: string | null
}

export interface UpdateOrganizationDto {
  name?: string
  slug?: string
  plan?: string
  is_active?: boolean
  custom_domain?: string | null
}
