import type { MenuDto, MenuListDto } from './api-types'
import type { Menu, MenuList } from './model'

export function mapMenuDtoToModel(dto: MenuDto): Menu {
  return {
    id: dto.id,
    name: dto.name,
    slug: dto.slug,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  }
}

export function mapMenuListDtoToModel(dto: MenuListDto): MenuList {
  return {
    items: dto.items.map(mapMenuDtoToModel),
  }
}
