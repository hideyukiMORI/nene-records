import type { ContentRegion } from '@/shared/lib/resolve-layout'
import type { WidgetType } from './model'

export interface WidgetDto {
  id: number
  widget_type: WidgetType
  region: ContentRegion
  display_order: number
  title: string | null
  settings: Record<string, unknown>
  created_at: string
  updated_at: string
}

export interface WidgetListDto {
  items: WidgetDto[]
}

export interface WidgetRequestDto {
  widget_type: WidgetType
  region: ContentRegion
  display_order: number
  title: string | null
  settings: Record<string, unknown>
}
