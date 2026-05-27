import type { OrgImportResultDto } from './api-types'
import type { OrgImportResult } from './model'

export function mapOrgImportResultDtoToModel(dto: OrgImportResultDto): OrgImportResult {
  return {
    organizationId: dto.organization_id,
    organizationName: dto.organization_name,
    total: dto.total,
    imported: dto.imported,
  }
}
