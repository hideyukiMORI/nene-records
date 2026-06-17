import type { WidgetType } from '@/entities/widget'
import type { MessageKey } from '@/shared/i18n'

export type SettingEditor = 'menu' | 'enum' | 'int' | 'text' | 'bool'

export interface SettingDescriptor {
  key: string
  labelKey: MessageKey
  editor: SettingEditor
  options?: readonly string[]
  def?: string | number
}

export interface WidgetCatalogEntry {
  type: WidgetType
  labelKey: MessageKey
  descKey: MessageKey
  settings: readonly SettingDescriptor[]
}

/**
 * Widget catalog: drives the palette, the inspector's settings editor, and the
 * add modal. Mirrors the backend WidgetTypes whitelist + DATA-MODEL settings.
 */
export const WIDGET_CATALOG: readonly WidgetCatalogEntry[] = [
  {
    type: 'menu',
    labelKey: 'admin.widgets.type.menu',
    descKey: 'admin.widgets.menuSettings',
    settings: [{ key: 'menuId', labelKey: 'admin.widgets.menuLabel', editor: 'menu' }],
  },
  {
    type: 'recent-posts',
    labelKey: 'admin.widgets.type.recent-posts',
    descKey: 'admin.widgets.recentPostsSettings',
    settings: [
      { key: 'entityTypeSlug', labelKey: 'admin.widgets.entityTypeLabel', editor: 'enum' },
      { key: 'limit', labelKey: 'admin.widgets.limitLabel', editor: 'int', def: 5 },
      { key: 'showDate', labelKey: 'admin.widgets.showDateLabel', editor: 'bool' },
      { key: 'showExcerpt', labelKey: 'admin.widgets.showExcerptLabel', editor: 'bool' },
    ],
  },
  {
    type: 'search',
    labelKey: 'admin.widgets.type.search',
    descKey: 'admin.widgets.searchSettings',
    settings: [
      { key: 'placeholder', labelKey: 'admin.widgets.searchPlaceholderLabel', editor: 'text' },
    ],
  },
  {
    type: 'tag-cloud',
    labelKey: 'admin.widgets.type.tag-cloud',
    descKey: 'admin.widgets.tagCloudSettings',
    settings: [],
  },
  {
    type: 'popular-posts',
    labelKey: 'admin.widgets.type.popular-posts',
    descKey: 'admin.widgets.popularPostsSettings',
    settings: [
      { key: 'limit', labelKey: 'admin.widgets.limitLabel', editor: 'int', def: 5 },
      { key: 'showDate', labelKey: 'admin.widgets.showDateLabel', editor: 'bool' },
    ],
  },
  {
    type: 'calendar',
    labelKey: 'admin.widgets.type.calendar',
    descKey: 'admin.widgets.calendarSettings',
    settings: [],
  },
  {
    type: 'toc',
    labelKey: 'admin.widgets.type.toc',
    descKey: 'admin.widgets.tocSettings',
    settings: [],
  },
]

export const WIDGET_CATALOG_BY_TYPE: Record<WidgetType, WidgetCatalogEntry> = Object.fromEntries(
  WIDGET_CATALOG.map((c) => [c.type, c]),
) as Record<WidgetType, WidgetCatalogEntry>
