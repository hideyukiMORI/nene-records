export interface Menu {
  id: number
  name: string
  slug: string
  createdAt: string
  updatedAt: string
}

export interface MenuList {
  items: Menu[]
}

export interface CreateMenuInput {
  name: string
}

export interface UpdateMenuInput {
  name: string
}
