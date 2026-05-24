import type { PublicSettingListDto } from '@/entities/setting/api-types'

const BOOTSTRAP_SCRIPT_ID = 'nene-records-public-record-bootstrap'

export interface PublicRecordBootstrapFieldListDto {
  items: unknown[]
  limit: number
  offset: number
}

export interface PublicRecordBootstrapRelationQuery {
  fieldKey: string
  items: Array<{ field_key: string; target_entity_id: number }>
}

export interface PublicRecordBootstrapDto {
  entityTypeSlug: string
  entityTypeId: number
  entityId: number
  entityTypes: PublicRecordBootstrapFieldListDto
  entity: {
    id: number
    entity_type_id: number
    is_deleted: boolean
    deleted_at: string | null
  }
  fieldDefs: PublicRecordBootstrapFieldListDto
  textFields: PublicRecordBootstrapFieldListDto
  intFields: PublicRecordBootstrapFieldListDto
  enumFields: PublicRecordBootstrapFieldListDto
  boolFields: PublicRecordBootstrapFieldListDto
  dateTimeFields: PublicRecordBootstrapFieldListDto
  entityRelations: PublicRecordBootstrapRelationQuery[]
  relationTextFieldsByEntityTypeId: Record<string, PublicRecordBootstrapFieldListDto>
  publicSettings?: PublicSettingListDto
}

export function readPublicRecordBootstrap(): PublicRecordBootstrapDto | null {
  if (typeof document === 'undefined') {
    return null
  }

  const element = document.getElementById(BOOTSTRAP_SCRIPT_ID)

  if (element === null) {
    return null
  }

  const raw = element.textContent.trim()

  if (raw === '') {
    return null
  }

  try {
    return JSON.parse(raw) as PublicRecordBootstrapDto
  } catch {
    return null
  }
}
