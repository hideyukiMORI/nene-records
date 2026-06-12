import type { WidgetDto, WidgetListDto } from './api-types'
import type { Widget, WidgetList } from './model'

export function mapWidgetDtoToModel(dto: WidgetDto): Widget {
  return {
    id: dto.id,
    widgetType: dto.widget_type,
    region: dto.region,
    displayOrder: dto.display_order,
    title: dto.title,
    settings: dto.settings,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  }
}

export function mapWidgetListDtoToModel(dto: WidgetListDto): WidgetList {
  return {
    items: dto.items.map(mapWidgetDtoToModel),
  }
}
