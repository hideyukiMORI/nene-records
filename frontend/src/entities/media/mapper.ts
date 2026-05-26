import type { MediaDto } from './api-types'
import type { Media } from './model'

export function mapMediaDtoToModel(dto: MediaDto): Media {
  return {
    id: dto.id,
    url: dto.url,
    originalName: dto.original_name,
    mimeType: dto.mime_type,
    size: dto.size,
  }
}
