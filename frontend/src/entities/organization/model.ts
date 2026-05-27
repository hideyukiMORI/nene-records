export interface Organization {
  id: number
  name: string
  slug: string
  customDomain: string | null
  plan: string
  isActive: boolean
  createdAt: string | null
  updatedAt: string | null
}

export interface OrganizationList {
  items: Organization[]
  total: number
  limit: number
  offset: number
}

export interface CreateOrganizationInput {
  name: string
  slug: string
  plan: string
  customDomain?: string | null
}

export interface UpdateOrganizationInput {
  name?: string
  slug?: string
  plan?: string
  isActive?: boolean
  customDomain?: string | null
}

export const PLANS = ['free', 'starter', 'pro', 'enterprise'] as const
export type Plan = (typeof PLANS)[number]
