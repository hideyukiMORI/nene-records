import type {
  CreateEntityTypeDto,
  EntityTypeDto,
  EntityTypeListDto,
  UpdateEntityTypeDto,
} from './api-types'
import { toEntityTypeId } from './ids'
import type {
  CreateEntityTypeInput,
  EntityType,
  EntityTypeList,
  UpdateEntityTypeInput,
} from './model'

export function mapEntityTypeDtoToModel(dto: EntityTypeDto): EntityType {
  return {
    id: toEntityTypeId(dto.id),
    name: dto.name,
    slug: dto.slug,
    isPinned: dto.is_pinned,
    defaultLayout: dto.default_layout ?? 'standard',
    displayOrder: dto.display_order ?? 0,
    labels: dto.labels && Object.keys(dto.labels).length > 0 ? dto.labels : undefined,
    permalinkPattern: dto.permalink_pattern ?? null,
    previousPermalinkPattern: dto.previous_permalink_pattern ?? null,
  }
}

export function mapEntityTypeListDtoToModel(dto: EntityTypeListDto): EntityTypeList {
  return {
    items: dto.items.map(mapEntityTypeDtoToModel),
    limit: dto.limit,
    offset: dto.offset,
  }
}

export function mapCreateInputToDto(input: CreateEntityTypeInput): CreateEntityTypeDto {
  return {
    name: input.name,
    slug: input.slug,
    is_pinned: input.isPinned ?? false,
  }
}

export function mapUpdateInputToDto(input: UpdateEntityTypeInput): UpdateEntityTypeDto {
  return {
    name: input.name,
    slug: input.slug,
    is_pinned: input.isPinned ?? false,
    labels: input.labels,
    permalink_pattern: input.permalinkPattern,
    ...(input.defaultLayout !== undefined ? { default_layout: input.defaultLayout } : {}),
  }
}
