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
  }
}

export function mapUpdateInputToDto(input: UpdateEntityTypeInput): UpdateEntityTypeDto {
  return mapCreateInputToDto(input)
}
