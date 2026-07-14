import type { WidgetRegion } from '@/shared/lib/resolve-layout'

export type WidgetType =
  | 'recent-posts'
  | 'menu'
  | 'toc'
  | 'search'
  | 'tag-cloud'
  | 'popular-posts'
  | 'calendar'
  | 'trusted-embed'

export interface Widget {
  id: number
  widgetType: WidgetType
  region: WidgetRegion
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
  region: WidgetRegion
  displayOrder: number
  title: string | null
  settings: Record<string, unknown>
}
