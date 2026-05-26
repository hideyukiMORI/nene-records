export interface Media {
  id: number
  url: string
  originalName: string
  mimeType: string
  size: number
  createdAt: string
}

export interface MediaList {
  items: Media[]
}
