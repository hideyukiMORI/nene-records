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
