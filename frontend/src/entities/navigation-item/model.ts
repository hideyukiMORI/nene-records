export type NavLocation = 'header' | 'footer' | 'side'

export const NAV_LOCATIONS: readonly NavLocation[] = ['header', 'footer', 'side']

export interface NavigationItem {
  id: number
  label: string
  url: string
  location: NavLocation
  displayOrder: number
  createdAt: string
  updatedAt: string
}

export interface NavigationItemList {
  items: NavigationItem[]
}

export interface CreateNavigationItemInput {
  label: string
  url: string
  location: NavLocation
  displayOrder: number
}

export interface UpdateNavigationItemInput {
  label: string
  url: string
  location: NavLocation
  displayOrder: number
}
