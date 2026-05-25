import type {
  CreateEntityDto,
  EntityDto,
  EntityListDto,
  EntityRevisionDto,
  EntityRevisionListDto,
  UpdateEntityDto,
} from './api-types'
import { toEntityId } from './ids'
import type {
  CreateEntityInput,
  Entity,
  EntityList,
  EntityRevision,
  EntityRevisionList,
  UpdateEntityInput,
} from './model'

export function mapEntityDtoToModel(dto: EntityDto): Entity {
  return {
    id: toEntityId(dto.id),
    entityTypeId: dto.entity_type_id,
    slug: dto.slug,
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
    ...(input.slug !== undefined ? { slug: input.slug } : {}),
    ...(input.status !== undefined ? { status: input.status } : {}),
  }
}

export function mapUpdateInputToDto(input: UpdateEntityInput): UpdateEntityDto {
  return {
    entity_type_id: input.entityTypeId,
    ...(input.slug !== undefined ? { slug: input.slug } : {}),
    status: input.status,
    ...(input.publishedAt !== undefined ? { published_at: input.publishedAt } : {}),
  }
}

export function mapEntityRevisionDtoToModel(dto: EntityRevisionDto): EntityRevision {
  return {
    id: dto.id,
    entityId: dto.entity_id,
    action: dto.action,
    status: dto.status,
    previousStatus: dto.previous_status,
    slug: dto.slug,
    previousSlug: dto.previous_slug,
    actorUserId: dto.actor_user_id,
    createdAt: dto.created_at,
  }
}

export function mapEntityRevisionListDtoToModel(dto: EntityRevisionListDto): EntityRevisionList {
  return {
    items: dto.items.map(mapEntityRevisionDtoToModel),
    limit: dto.limit,
    offset: dto.offset,
  }
}
