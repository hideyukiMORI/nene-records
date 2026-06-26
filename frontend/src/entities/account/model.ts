export interface AccountEntitlements {
  /** Whether the plan allows a custom domain. */
  customDomainAllowed: boolean
  /** null = unlimited. */
  maxRecords: number | null
  maxStorageBytes: number | null
  maxAdminUsers: number | null
}

export interface Account {
  slug: string
  name: string
  plan: string
  customDomain: string | null
  entitlements: AccountEntitlements
  recordsUsed: number
}
