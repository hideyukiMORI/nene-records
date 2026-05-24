import type {
  CreateIntFieldDto,
  IntFieldDto,
  IntFieldListDto,
  UpdateIntFieldDto,
} from './api-types'
import { toIntFieldId } from './ids'
import type { CreateIntFieldInput, IntField, IntFieldList, UpdateIntFieldInput } from './model'

export function mapIntFieldDtoToModel(dto: IntFieldDto): IntField {
  return {
    id: toIntFieldId(dto.id),
    entityId: dto.entity_id,
    fieldKey: dto.field_key,
    value: dto.value,
  }
}

export function mapIntFieldListDtoToModel(dto: IntFieldListDto): IntFieldList {
  return {
    items: dto.items.map(mapIntFieldDtoToModel),
    limit: dto.limit,
    offset: dto.offset,
  }
}

export function mapCreateInputToDto(input: CreateIntFieldInput): CreateIntFieldDto {
  return {
    entity_id: input.entityId,
    field_key: input.fieldKey,
    value: input.value,
  }
}

export function mapUpdateInputToDto(input: UpdateIntFieldInput): UpdateIntFieldDto {
  return {
    field_key: input.fieldKey,
    value: input.value,
  }
}
