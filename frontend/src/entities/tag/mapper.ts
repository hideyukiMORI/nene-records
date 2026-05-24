import type { CreateTagDto, TagDto, TagListDto, UpdateTagDto } from './api-types'
import { toTagId } from './ids'
import type { CreateTagInput, Tag, TagList, UpdateTagInput } from './model'

export function mapTagDtoToModel(dto: TagDto): Tag {
  return {
    id: toTagId(dto.id),
    name: dto.name,
    slug: dto.slug,
  }
}

export function mapTagListDtoToModel(dto: TagListDto): TagList {
  return {
    items: dto.items.map(mapTagDtoToModel),
    limit: dto.limit,
    offset: dto.offset,
  }
}

export function mapCreateInputToDto(input: CreateTagInput): CreateTagDto {
  return {
    name: input.name,
    slug: input.slug,
  }
}

export function mapUpdateInputToDto(input: UpdateTagInput): UpdateTagDto {
  return mapCreateInputToDto(input)
}
