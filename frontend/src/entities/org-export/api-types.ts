export interface OrgImportResultDto {
  organization_id: number
  organization_name: string
  total: number
  imported: Record<string, number>
}
