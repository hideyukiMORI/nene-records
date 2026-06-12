export interface Media {
  id: number
  url: string
  originalName: string
  mimeType: string
  size: number
  width: number | null
  height: number | null
  altText: string | null
  createdAt: string
}

export interface MediaList {
  items: Media[]
}

export interface MediaUsage {
  entityId: number
  entityTypeSlug: string
  entitySlug: string
  status: string
  fieldKey: string
  title: string | null
}

export interface MediaUsageList {
  items: MediaUsage[]
}
