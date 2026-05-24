import type {
  BoolFieldDto,
  BoolFieldListDto,
  CreateBoolFieldDto,
  UpdateBoolFieldDto,
} from './api-types'
import { toBoolFieldId } from './ids'
import type { BoolField, BoolFieldList, CreateBoolFieldInput, UpdateBoolFieldInput } from './model'

export function mapBoolFieldDtoToModel(dto: BoolFieldDto): BoolField {
  return {
    id: toBoolFieldId(dto.id),
    entityId: dto.entity_id,
    fieldKey: dto.field_key,
    value: dto.value,
  }
}

export function mapBoolFieldListDtoToModel(dto: BoolFieldListDto): BoolFieldList {
  return {
    items: dto.items.map(mapBoolFieldDtoToModel),
    limit: dto.limit,
    offset: dto.offset,
  }
}

export function mapCreateInputToDto(input: CreateBoolFieldInput): CreateBoolFieldDto {
  return {
    entity_id: input.entityId,
    field_key: input.fieldKey,
    value: input.value,
  }
}

export function mapUpdateInputToDto(input: UpdateBoolFieldInput): UpdateBoolFieldDto {
  return {
    field_key: input.fieldKey,
    value: input.value,
  }
}
