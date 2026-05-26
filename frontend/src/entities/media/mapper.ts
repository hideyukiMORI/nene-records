import type { MediaDto, MediaListDto } from './api-types'
import type { Media, MediaList } from './model'

export function mapMediaDtoToModel(dto: MediaDto): Media {
  return {
    id: dto.id,
    url: dto.url,
    originalName: dto.original_name,
    mimeType: dto.mime_type,
    size: dto.size,
    createdAt: dto.created_at,
  }
}

export function mapMediaListDtoToModel(dto: MediaListDto): MediaList {
  return {
    items: dto.items.map(mapMediaDtoToModel),
  }
}
