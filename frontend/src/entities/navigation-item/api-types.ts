import type { NavLocation } from './model'

export interface NavigationItemDto {
  id: number
  label: string
  url: string
  location: NavLocation
  menu_id: number | null
  display_order: number
  created_at: string
  updated_at: string
}

export interface NavigationItemListDto {
  items: NavigationItemDto[]
}

export interface CreateNavigationItemRequestDto {
  label: string
  url: string
  location: NavLocation
  display_order: number
}

export interface UpdateNavigationItemRequestDto {
  label: string
  url: string
  location: NavLocation
  display_order: number
}
