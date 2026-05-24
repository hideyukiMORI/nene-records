import type {
  CreateFieldDefDto,
  FieldDefDto,
  FieldDefListDto,
  UpdateFieldDefDto,
} from './api-types'
import { isFieldDataType } from './enum'
import { toFieldDefId } from './ids'
import type { CreateFieldDefInput, FieldDef, FieldDefList, UpdateFieldDefInput } from './model'

function mapDataType(value: string): FieldDef['dataType'] {
  if (!isFieldDataType(value)) {
    throw new Error(`Unsupported field data type: ${value}`)
  }

  return value
}

export function mapFieldDefDtoToModel(dto: FieldDefDto): FieldDef {
  return {
    id: toFieldDefId(dto.id),
    entityTypeId: dto.entity_type_id,
    fieldKey: dto.field_key,
    dataType: mapDataType(dto.data_type),
  }
}

export function mapFieldDefListDtoToModel(dto: FieldDefListDto): FieldDefList {
  return {
    items: dto.items.map(mapFieldDefDtoToModel),
    limit: dto.limit,
    offset: dto.offset,
  }
}

export function mapCreateInputToDto(input: CreateFieldDefInput): CreateFieldDefDto {
  return {
    entity_type_id: input.entityTypeId,
    field_key: input.fieldKey,
    data_type: input.dataType,
  }
}

export function mapUpdateInputToDto(input: UpdateFieldDefInput): UpdateFieldDefDto {
  return mapCreateInputToDto(input)
}
