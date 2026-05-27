import type { DataMigrationStatusDto, AssignOrgResultDto } from './api-types'
import type { DataMigrationStatus, AssignOrgResult } from './model'

export function mapDataMigrationStatusDtoToModel(dto: DataMigrationStatusDto): DataMigrationStatus {
  return {
    total: dto.total,
    tables: dto.tables,
  }
}

export function mapAssignOrgResultDtoToModel(dto: AssignOrgResultDto): AssignOrgResult {
  return {
    organizationId: dto.organization_id,
    organizationName: dto.organization_name,
    total: dto.total,
    tables: dto.tables,
  }
}
