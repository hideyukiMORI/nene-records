export interface NavigationItem {
  id: number
  label: string
  url: string
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
  displayOrder: number
}

export interface UpdateNavigationItemInput {
  label: string
  url: string
  displayOrder: number
}
