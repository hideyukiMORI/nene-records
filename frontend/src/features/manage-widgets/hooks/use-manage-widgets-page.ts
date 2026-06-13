import { useCallback, useMemo, useState } from 'react'
import { useEntityTypeList } from '@/entities/entity-type'
import { useMenuList, type Menu } from '@/entities/menu'
import {
  useCreateWidget,
  useDeleteWidget,
  useUpdateWidget,
  useWidgetList,
  type Widget,
  type WidgetInput,
  type WidgetType,
} from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import type { WidgetRegion } from '@/shared/lib/resolve-layout'
import { useToast } from '@/shared/ui'

/** Default settings for a freshly added widget of `type`. */
function defaultSettings(type: WidgetType, menus: Menu[]): Record<string, unknown> {
  switch (type) {
    case 'menu':
      return { menuId: menus[0]?.id ?? null }
    case 'recent-posts':
      return { entityTypeSlug: '', limit: 5 }
    case 'popular-posts':
      return { limit: 5 }
    case 'search':
      return { placeholder: '' }
    default:
      return {}
  }
}

function toInput(w: Widget, overrides: Partial<WidgetInput> = {}): WidgetInput {
  return {
    widgetType: w.widgetType,
    region: w.region,
    displayOrder: w.displayOrder,
    title: w.title,
    settings: w.settings,
    ...overrides,
  }
}

/**
 * Layout-tab state: the widget list plus drag-and-drop placement operations
 * (add at index, move/reorder across regions) and inspector edits, all persisted
 * through the widget API.
 */
export function useManageWidgetsPage() {
  const listQuery = useWidgetList()
  const entityTypesQuery = useEntityTypeList()
  const menusQuery = useMenuList()
  const createMutation = useCreateWidget()
  const updateMutation = useUpdateWidget()
  const deleteMutation = useDeleteWidget()
  const { showToast } = useToast()
  const { t } = useTranslation()

  const [selectedId, setSelectedId] = useState<number | null>(null)
  const widgets = useMemo(() => listQuery.data?.items ?? [], [listQuery.data?.items])
  const menus = useMemo(() => menusQuery.data?.items ?? [], [menusQuery.data?.items])

  const remove = useCallback(
    async (id: number) => {
      await deleteMutation.mutateAsync(id)
      setSelectedId((s) => (s === id ? null : s))
      showToast(t('admin.layout.toast.removed'))
    },
    [deleteMutation, showToast, t],
  )

  // Persist a region's desired widget order; only PUT widgets whose region or
  // display_order actually changed.
  const persistOrder = useCallback(
    async (region: WidgetRegion, ordered: Widget[]) => {
      await Promise.all(
        ordered.map((w, index) =>
          w.region === region && w.displayOrder === index
            ? Promise.resolve(undefined)
            : updateMutation.mutateAsync({
                id: w.id,
                input: toInput(w, { region, displayOrder: index }),
              }),
        ),
      )
    },
    [updateMutation],
  )

  const addWidgetAt = useCallback(
    async (type: WidgetType, region: WidgetRegion, index: number | null) => {
      const created = await createMutation.mutateAsync({
        widgetType: type,
        region,
        displayOrder: 9999,
        title: null,
        settings: defaultSettings(type, menus),
      })
      const list = widgets.filter((w) => w.region === region)
      const at = index === null || index > list.length ? list.length : index
      const ordered = [...list]
      ordered.splice(at, 0, created)
      await persistOrder(region, ordered)
      setSelectedId(created.id)
      showToast(t('admin.layout.toast.added'))
    },
    [createMutation, menus, widgets, persistOrder, showToast, t],
  )

  const moveWidgetAt = useCallback(
    async (id: number, region: WidgetRegion, index: number | null) => {
      const moving = widgets.find((w) => w.id === id)
      if (moving === undefined) return
      const list = widgets.filter((w) => w.region === region && w.id !== id)
      const at = index === null || index > list.length ? list.length : index
      const ordered = [...list]
      ordered.splice(at, 0, moving)
      await persistOrder(region, ordered)
    },
    [widgets, persistOrder],
  )

  const updateSettings = useCallback(
    async (id: number, patch: Record<string, unknown>) => {
      const w = widgets.find((x) => x.id === id)
      if (w === undefined) return
      await updateMutation.mutateAsync({
        id,
        input: toInput(w, { settings: { ...w.settings, ...patch } }),
      })
    },
    [widgets, updateMutation],
  )

  const updateTitle = useCallback(
    async (id: number, title: string) => {
      const w = widgets.find((x) => x.id === id)
      if (w === undefined) return
      await updateMutation.mutateAsync({
        id,
        input: toInput(w, { title: title.trim() === '' ? null : title.trim() }),
      })
    },
    [widgets, updateMutation],
  )

  return {
    widgets,
    isLoading: listQuery.isLoading,
    entityTypes: entityTypesQuery.data?.items ?? [],
    menus,
    selectedId,
    isSubmitting: createMutation.isPending || updateMutation.isPending,
    select: setSelectedId,
    addWidgetAt,
    moveWidgetAt,
    updateSettings,
    updateTitle,
    remove,
  }
}
