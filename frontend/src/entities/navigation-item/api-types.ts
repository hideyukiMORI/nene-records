export interface NavigationItemDto {
  id: number
  label: string
  url: string
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
  display_order: number
}

export interface UpdateNavigationItemRequestDto {
  label: string
  url: string
  display_order: number
}
