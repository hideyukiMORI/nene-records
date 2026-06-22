import type {
  CreateFieldDefDto,
  FieldDefDto,
  FieldDefListDto,
  UpdateFieldDefDto,
} from './api-types'
import { isFieldDataType, isRelationCardinality } from './enum'
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
    ...(dto.target_entity_type_id !== undefined
      ? { targetEntityTypeId: dto.target_entity_type_id }
      : {}),
    ...(dto.cardinality !== undefined && isRelationCardinality(dto.cardinality)
      ? { cardinality: dto.cardinality }
      : {}),
    region: dto.region ?? null,
    displayOrder: dto.display_order ?? 0,
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
  const dto: CreateFieldDefDto = {
    entity_type_id: input.entityTypeId,
    field_key: input.fieldKey,
    data_type: input.dataType,
  }

  if (input.dataType === 'relation') {
    if (input.targetEntityTypeId !== undefined) {
      dto.target_entity_type_id = input.targetEntityTypeId
    }
    if (input.cardinality !== undefined) {
      dto.cardinality = input.cardinality
    }
  }

  if (input.region !== undefined) {
    dto.region = input.region
  }
  if (input.displayOrder !== undefined) {
    dto.display_order = input.displayOrder
  }

  return dto
}

export function mapUpdateInputToDto(input: UpdateFieldDefInput): UpdateFieldDefDto {
  return mapCreateInputToDto(input)
}
