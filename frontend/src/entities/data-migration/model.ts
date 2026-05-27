export interface DataMigrationStatus {
  total: number
  tables: Record<string, number>
}

export interface AssignOrgResult {
  organizationId: number
  organizationName: string
  total: number
  tables: Record<string, number>
}
