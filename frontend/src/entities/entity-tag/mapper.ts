import { toTagId } from '@/entities/tag'
import type { EntityTagDto, EntityTagListDto } from './api-types'
import type { AttachEntityTagInput, EntityTag, EntityTagList } from './model'

export function mapEntityTagDtoToModel(dto: EntityTagDto): EntityTag {
  return {
    id: toTagId(dto.id),
    slug: dto.slug,
    name: dto.name,
  }
}

export function mapEntityTagListDtoToModel(dto: EntityTagListDto): EntityTagList {
  return {
    items: dto.items.map(mapEntityTagDtoToModel),
  }
}

export function mapAttachInputToDto(input: AttachEntityTagInput): { tag_id: number } {
  return {
    tag_id: Number(input.tagId),
  }
}
