import { describe, expect, it } from 'vitest'
import { mapWidgetDtoToModel } from './mapper'

describe('widget mapper', () => {
  it('maps a widget dto to model', () => {
    const model = mapWidgetDtoToModel({
      id: 1,
      widget_type: 'recent-posts',
      region: 'sidebar',
      display_order: 2,
      title: 'Recent',
      settings: { entityTypeSlug: 'post', limit: 5 },
      created_at: '2026-01-01T00:00:00Z',
      updated_at: '2026-01-02T00:00:00Z',
    })

    expect(model).toEqual({
      id: 1,
      widgetType: 'recent-posts',
      region: 'sidebar',
      displayOrder: 2,
      title: 'Recent',
      settings: { entityTypeSlug: 'post', limit: 5 },
      createdAt: '2026-01-01T00:00:00Z',
      updatedAt: '2026-01-02T00:00:00Z',
    })
  })
})
