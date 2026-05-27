import { apiClient } from '@/shared/api/client'

/**
 * Fetches the full JSON export for an organization.
 * Returns the raw payload so callers can serialize and download it.
 */
export async function fetchOrgExport(orgId: number): Promise<Record<string, unknown>> {
  return apiClient.get<Record<string, unknown>>(
    `/api/v1/superadmin/organizations/${String(orgId)}/export`,
  )
}
