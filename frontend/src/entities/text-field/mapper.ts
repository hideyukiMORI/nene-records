import type {
  CreateTextFieldDto,
  TextFieldDto,
  TextFieldListDto,
  UpdateTextFieldDto,
} from './api-types'
import { toTextFieldId } from './ids'
import type { CreateTextFieldInput, TextField, TextFieldList, UpdateTextFieldInput } from './model'

export function mapTextFieldDtoToModel(dto: TextFieldDto): TextField {
  return {
    id: toTextFieldId(dto.id),
    entityId: dto.entity_id,
    fieldKey: dto.field_key,
    value: dto.value,
  }
}

export function mapTextFieldListDtoToModel(dto: TextFieldListDto): TextFieldList {
  return {
    items: dto.items.map(mapTextFieldDtoToModel),
    limit: dto.limit,
    offset: dto.offset,
  }
}

export function mapCreateInputToDto(input: CreateTextFieldInput): CreateTextFieldDto {
  return {
    entity_id: input.entityId,
    field_key: input.fieldKey,
    value: input.value,
  }
}

export function mapUpdateInputToDto(input: UpdateTextFieldInput): UpdateTextFieldDto {
  return {
    field_key: input.fieldKey,
    value: input.value,
  }
}
