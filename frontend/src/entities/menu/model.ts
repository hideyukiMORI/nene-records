export type MenuLocation = 'header' | 'footer'

export const MENU_LOCATIONS: readonly MenuLocation[] = ['header', 'footer']

export interface Menu {
  id: number
  name: string
  slug: string
  /** header / footer = auto-displayed; null = only via a menu widget. */
  location: MenuLocation | null
  createdAt: string
  updatedAt: string
}

export interface MenuList {
  items: Menu[]
}

export interface CreateMenuInput {
  name: string
  location: MenuLocation | null
}

export interface UpdateMenuInput {
  name: string
  location: MenuLocation | null
}
