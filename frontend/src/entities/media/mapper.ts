import type { MediaDto, MediaListDto, MediaUsageDto, MediaUsageListDto } from './api-types'
import type { Media, MediaList, MediaUsage, MediaUsageList } from './model'

export function mapMediaDtoToModel(dto: MediaDto): Media {
  return {
    id: dto.id,
    url: dto.url,
    originalName: dto.original_name,
    mimeType: dto.mime_type,
    size: dto.size,
    width: dto.width,
    height: dto.height,
    altText: dto.alt_text,
    createdAt: dto.created_at,
  }
}

export function mapMediaListDtoToModel(dto: MediaListDto): MediaList {
  return {
    items: dto.items.map(mapMediaDtoToModel),
  }
}

export function mapMediaUsageDtoToModel(dto: MediaUsageDto): MediaUsage {
  return {
    entityId: dto.entity_id,
    entityTypeSlug: dto.entity_type_slug,
    entitySlug: dto.entity_slug,
    status: dto.status,
    fieldKey: dto.field_key,
    title: dto.title,
  }
}

export function mapMediaUsageListDtoToModel(dto: MediaUsageListDto): MediaUsageList {
  return {
    items: dto.items.map(mapMediaUsageDtoToModel),
  }
}
