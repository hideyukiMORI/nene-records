export interface OrgImportResult {
  organizationId: number
  organizationName: string
  total: number
  imported: Record<string, number>
}
