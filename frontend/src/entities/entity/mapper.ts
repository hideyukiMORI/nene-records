import type { CreateEntityDto, EntityDto, EntityListDto, UpdateEntityDto } from './api-types'
import { toEntityId } from './ids'
import type { CreateEntityInput, Entity, EntityList, UpdateEntityInput } from './model'

export function mapEntityDtoToModel(dto: EntityDto): Entity {
  return {
    id: toEntityId(dto.id),
    entityTypeId: dto.entity_type_id,
    status: dto.status,
    publishedAt: dto.published_at,
    isDeleted: dto.is_deleted,
    deletedAt: dto.deleted_at,
  }
}

export function mapEntityListDtoToModel(dto: EntityListDto): EntityList {
  return {
    items: dto.items.map(mapEntityDtoToModel),
    limit: dto.limit,
    offset: dto.offset,
    total: dto.total,
  }
}

export function mapCreateInputToDto(input: CreateEntityInput): CreateEntityDto {
  return {
    entity_type_id: input.entityTypeId,
    ...(input.status !== undefined ? { status: input.status } : {}),
  }
}

export function mapUpdateInputToDto(input: UpdateEntityInput): UpdateEntityDto {
  return {
    entity_type_id: input.entityTypeId,
    status: input.status,
    ...(input.publishedAt !== undefined ? { published_at: input.publishedAt } : {}),
  }
}
