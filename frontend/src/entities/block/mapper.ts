import type {
  BlocksFieldDto,
  BlocksFieldListDto,
  CreateBlocksFieldDto,
  UpdateBlocksFieldDto,
} from './api-types'
import { toBlocksFieldId } from './ids'
import type {
  BlocksField,
  BlocksFieldList,
  CreateBlocksFieldInput,
  UpdateBlocksFieldInput,
} from './model'

export function mapBlocksFieldDtoToModel(dto: BlocksFieldDto): BlocksField {
  return {
    id: toBlocksFieldId(dto.id),
    entityId: dto.entity_id,
    fieldKey: dto.field_key,
    value: dto.value,
    locale: dto.locale,
  }
}

export function mapBlocksFieldListDtoToModel(dto: BlocksFieldListDto): BlocksFieldList {
  return {
    items: dto.items.map(mapBlocksFieldDtoToModel),
    limit: dto.limit,
    offset: dto.offset,
  }
}

export function mapCreateInputToDto(input: CreateBlocksFieldInput): CreateBlocksFieldDto {
  return {
    entity_id: input.entityId,
    field_key: input.fieldKey,
    value: input.value,
    ...(input.locale !== undefined ? { locale: input.locale } : {}),
  }
}

export function mapUpdateInputToDto(input: UpdateBlocksFieldInput): UpdateBlocksFieldDto {
  return {
    field_key: input.fieldKey,
    value: input.value,
    ...(input.locale !== undefined ? { locale: input.locale } : {}),
  }
}
