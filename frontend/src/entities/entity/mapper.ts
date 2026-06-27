import type {
  CreateEntityDto,
  EntityDto,
  EntityListDto,
  EntityRevisionDto,
  EntityRevisionListDto,
  GeneratePreviewTokenResponseDto,
  ScheduleEntityResponseDto,
  UpdateEntityDto,
} from './api-types'
import { toEntityId } from './ids'
import type {
  CreateEntityInput,
  Entity,
  EntityList,
  EntityRevision,
  EntityRevisionList,
  GeneratePreviewTokenOutput,
  ScheduleEntityOutput,
  UpdateEntityInput,
} from './model'

export function mapEntityDtoToModel(dto: EntityDto): Entity {
  return {
    id: toEntityId(dto.id),
    entityTypeId: dto.entity_type_id,
    slug: dto.slug,
    permalink: dto.permalink ?? null,
    layout: dto.layout ?? null,
    status: dto.status,
    publishedAt: dto.published_at,
    scheduledAt: dto.scheduled_at,
    isDeleted: dto.is_deleted,
    deletedAt: dto.deleted_at,
    metaTitle: dto.meta_title,
    metaDescription: dto.meta_description,
    menuOrder: dto.menu_order ?? 0,
    ...(dto.excerpt !== undefined ? { excerpt: dto.excerpt } : {}),
    ...(dto.view_count !== undefined && dto.view_count !== null
      ? { viewCount: dto.view_count }
      : {}),
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  }
}

export function mapScheduleResponseDtoToOutput(
  dto: ScheduleEntityResponseDto,
): ScheduleEntityOutput {
  return {
    id: dto.id,
    status: dto.status,
    scheduledAt: dto.scheduled_at,
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
    ...(input.permalink !== undefined ? { permalink: input.permalink } : {}),
    ...(input.status !== undefined ? { status: input.status } : {}),
    ...(input.layout !== undefined ? { layout: input.layout } : {}),
  }
}

export function mapUpdateInputToDto(input: UpdateEntityInput): UpdateEntityDto {
  return {
    entity_type_id: input.entityTypeId,
    ...(input.slug !== undefined ? { slug: input.slug } : {}),
    ...(input.permalink !== undefined ? { permalink: input.permalink } : {}),
    status: input.status,
    ...(input.publishedAt !== undefined ? { published_at: input.publishedAt } : {}),
    ...(input.metaTitle !== undefined ? { meta_title: input.metaTitle } : {}),
    ...(input.metaDescription !== undefined ? { meta_description: input.metaDescription } : {}),
    ...(input.layout !== undefined ? { layout: input.layout } : {}),
  }
}

export function mapGeneratePreviewTokenResponseDtoToOutput(
  dto: GeneratePreviewTokenResponseDto,
): GeneratePreviewTokenOutput {
  return {
    token: dto.token,
    expiresAt: dto.expires_at,
    previewUrl: dto.preview_url,
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
