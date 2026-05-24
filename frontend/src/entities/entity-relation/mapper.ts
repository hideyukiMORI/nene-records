import type { EntityRelationItemDto, EntityRelationListDto } from './api-types'
import type { AttachEntityRelationInput, EntityRelation, EntityRelationList } from './model'

export function mapEntityRelationItemDtoToModel(dto: EntityRelationItemDto): EntityRelation {
  return {
    fieldKey: dto.field_key,
    targetEntityId: dto.target_entity_id,
  }
}

export function mapEntityRelationListDtoToModel(dto: EntityRelationListDto): EntityRelationList {
  return {
    items: dto.items.map(mapEntityRelationItemDtoToModel),
  }
}

export function mapAttachInputToDto(input: AttachEntityRelationInput): {
  field_key: string
  target_entity_id: number
} {
  return {
    field_key: input.fieldKey,
    target_entity_id: input.targetEntityId,
  }
}
