import type { NavigationItemDto, NavigationItemListDto } from './api-types'
import type { NavigationItem, NavigationItemList } from './model'

export function mapNavigationItemDtoToModel(dto: NavigationItemDto): NavigationItem {
  return {
    id: dto.id,
    label: dto.label,
    url: dto.url,
    location: dto.location,
    displayOrder: dto.display_order,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  }
}

export function mapNavigationItemListDtoToModel(dto: NavigationItemListDto): NavigationItemList {
  return {
    items: dto.items.map(mapNavigationItemDtoToModel),
  }
}
