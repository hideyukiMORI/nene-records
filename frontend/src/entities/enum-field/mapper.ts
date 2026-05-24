import type {
  CreateEnumFieldDto,
  EnumFieldDto,
  EnumFieldListDto,
  UpdateEnumFieldDto,
} from './api-types'
import { toEnumFieldId } from './ids'
import type { CreateEnumFieldInput, EnumField, EnumFieldList, UpdateEnumFieldInput } from './model'

export function mapEnumFieldDtoToModel(dto: EnumFieldDto): EnumField {
  return {
    id: toEnumFieldId(dto.id),
    entityId: dto.entity_id,
    fieldKey: dto.field_key,
    value: dto.value,
  }
}

export function mapEnumFieldListDtoToModel(dto: EnumFieldListDto): EnumFieldList {
  return {
    items: dto.items.map(mapEnumFieldDtoToModel),
    limit: dto.limit,
    offset: dto.offset,
  }
}

export function mapCreateInputToDto(input: CreateEnumFieldInput): CreateEnumFieldDto {
  return {
    entity_id: input.entityId,
    field_key: input.fieldKey,
    value: input.value,
  }
}

export function mapUpdateInputToDto(input: UpdateEnumFieldInput): UpdateEnumFieldDto {
  return {
    field_key: input.fieldKey,
    value: input.value,
  }
}
