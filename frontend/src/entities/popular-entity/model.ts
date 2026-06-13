export interface PopularEntity {
  entityId: number
  entityTypeId: number
  slug: string | null
  publishedAt: string | null
  title: string | null
  viewCount: number
}

export interface PopularEntityList {
  items: PopularEntity[]
}
