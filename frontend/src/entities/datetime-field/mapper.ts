import type {
  CreateDateTimeFieldDto,
  DateTimeFieldDto,
  DateTimeFieldListDto,
  UpdateDateTimeFieldDto,
} from './api-types'
import { toDateTimeFieldId } from './ids'
import type {
  CreateDateTimeFieldInput,
  DateTimeField,
  DateTimeFieldList,
  UpdateDateTimeFieldInput,
} from './model'

export function mapDateTimeFieldDtoToModel(dto: DateTimeFieldDto): DateTimeField {
  return {
    id: toDateTimeFieldId(dto.id),
    entityId: dto.entity_id,
    fieldKey: dto.field_key,
    value: dto.value,
  }
}

export function mapDateTimeFieldListDtoToModel(dto: DateTimeFieldListDto): DateTimeFieldList {
  return {
    items: dto.items.map(mapDateTimeFieldDtoToModel),
    limit: dto.limit,
    offset: dto.offset,
  }
}

export function mapCreateInputToDto(input: CreateDateTimeFieldInput): CreateDateTimeFieldDto {
  return {
    entity_id: input.entityId,
    field_key: input.fieldKey,
    value: input.value,
  }
}

export function mapUpdateInputToDto(input: UpdateDateTimeFieldInput): UpdateDateTimeFieldDto {
  return {
    field_key: input.fieldKey,
    value: input.value,
  }
}
