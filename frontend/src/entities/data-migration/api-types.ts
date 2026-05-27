export interface DataMigrationStatusDto {
  total: number
  tables: Record<string, number>
}

export interface AssignOrgResultDto {
  organization_id: number
  organization_name: string
  total: number
  tables: Record<string, number>
}
