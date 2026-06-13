import type { PopularEntitiesResponseDto, PopularEntityItemDto } from './api-types'
import type { PopularEntity, PopularEntityList } from './model'

export function mapPopularEntityDtoToModel(dto: PopularEntityItemDto): PopularEntity {
  return {
    entityId: dto.entity_id,
    entityTypeId: dto.entity_type_id,
    slug: dto.slug,
    publishedAt: dto.published_at,
    title: dto.title,
    viewCount: dto.view_count,
  }
}

export function mapPopularEntityListDtoToModel(dto: PopularEntitiesResponseDto): PopularEntityList {
  return {
    items: dto.items.map(mapPopularEntityDtoToModel),
  }
}
