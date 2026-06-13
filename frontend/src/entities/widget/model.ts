import type { ContentRegion } from '@/shared/lib/resolve-layout'

export type WidgetType = 'recent-posts' | 'menu' | 'toc' | 'search'

export interface Widget {
  id: number
  widgetType: WidgetType
  region: ContentRegion
  displayOrder: number
  title: string | null
  settings: Record<string, unknown>
  createdAt: string
  updatedAt: string
}

export interface WidgetList {
  items: Widget[]
}

export interface WidgetInput {
  widgetType: WidgetType
  region: ContentRegion
  displayOrder: number
  title: string | null
  settings: Record<string, unknown>
}
